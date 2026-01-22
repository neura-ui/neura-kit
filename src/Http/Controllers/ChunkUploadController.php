<?php

namespace Neura\Kit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ChunkUploadController extends Controller
{
    /**
     * Upload chunk endpoint
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'chunk' => 'required|file',
            'chunkIndex' => 'required|integer|min:0',
            'totalChunks' => 'required|integer|min:1',
            'uuid' => 'required|string|uuid',
            'fileName' => 'required|string|max:255',
            'fileSize' => 'required|integer|min:1',
            'field' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $chunkIndex = (int) $request->input('chunkIndex');
        $totalChunks = (int) $request->input('totalChunks');
        $uuid = $request->input('uuid');
        $fileName = $request->input('fileName');
        $fileSize = (int) $request->input('fileSize');
        
        // Sécurité : vérifier la taille totale du fichier
        $maxSize = config('neura-kit.upload.max_size', 100); // MB
        if ($fileSize > ($maxSize * 1024 * 1024)) {
            return response()->json([
                'success' => false,
                'message' => "File size exceeds maximum allowed size of {$maxSize}MB",
            ], 413);
        }

        $fileName = $this->sanitizeFileName($fileName);

        try {
            $tmpDir = config('livewire.temporary_file_upload.disk') ?? 'local';
            $chunkDir = "livewire-tmp/chunks/{$uuid}";
            
            // Stocker le chunk
            $chunk = $request->file('chunk');
            Storage::disk($tmpDir)->putFileAs(
                $chunkDir,
                $chunk,
                "chunk_{$chunkIndex}"
            );

            if ($chunkIndex === $totalChunks - 1) {
                return $this->assembleChunks($tmpDir, $chunkDir, $fileName, $totalChunks, $uuid);
            }

            return response()->json([
                'success' => true,
                'message' => "Chunk {$chunkIndex} uploaded successfully",
            ]);

        } catch (\Exception $e) {
            // Nettoyage en cas d'erreur
            $this->cleanupChunks($tmpDir, $chunkDir);
            
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assemble chunks into final file
     */
    protected function assembleChunks(string $disk, string $chunkDir, string $fileName, int $totalChunks, string $uuid)
    {
        try {
            // Créer le fichier final dans le dossier tmp Livewire
            $finalPath = "livewire-tmp/{$uuid}";
            $finalFullPath = Storage::disk($disk)->path($finalPath);
            
            // Créer le dossier si nécessaire
            $dir = dirname($finalFullPath);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            // Ouvrir le fichier final en écriture
            $output = fopen($finalFullPath, 'wb');
            if (!$output) {
                throw new \Exception('Failed to create output file');
            }

            // Assembler tous les chunks
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = Storage::disk($disk)->path("{$chunkDir}/chunk_{$i}");
                
                if (!file_exists($chunkPath)) {
                    fclose($output);
                    throw new \Exception("Chunk {$i} is missing");
                }

                $chunkHandle = fopen($chunkPath, 'rb');
                if (!$chunkHandle) {
                    fclose($output);
                    throw new \Exception("Failed to read chunk {$i}");
                }

                stream_copy_to_stream($chunkHandle, $output);
                fclose($chunkHandle);
            }

            fclose($output);

            // Nettoyage des chunks
            $this->cleanupChunks($disk, $chunkDir);

            // Créer un fichier de métadonnées (comme Livewire)
            $metadata = [
                'filename' => $fileName,
                'size' => filesize($finalFullPath),
                'mime' => mime_content_type($finalFullPath),
                'path' => $finalPath,
                'uploaded_at' => now()->timestamp,
            ];

            Storage::disk($disk)->put(
                "{$finalPath}.meta",
                json_encode($metadata)
            );

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => [
                    'uuid' => $uuid,
                    'filename' => $fileName,
                    'path' => $finalPath,
                    'size' => $metadata['size'],
                    'mime' => $metadata['mime'],
                ],
            ]);

        } catch (\Exception $e) {
            // Nettoyage en cas d'erreur
            $this->cleanupChunks($disk, $chunkDir);
            
            if (isset($finalFullPath) && file_exists($finalFullPath)) {
                @unlink($finalFullPath);
            }

            throw $e;
        }
    }

    /**
     * Cleanup chunk files
     */
    protected function cleanupChunks(string $disk, string $chunkDir): void
    {
        try {
            if (Storage::disk($disk)->exists($chunkDir)) {
                Storage::disk($disk)->deleteDirectory($chunkDir);
            }
        } catch (\Exception $e) {
            // Log mais ne pas bloquer
            logger()->warning("Failed to cleanup chunks: {$e->getMessage()}");
        }
    }

    /**
     * Sanitize filename to prevent directory traversal
     */
    protected function sanitizeFileName(string $fileName): string
    {
        // Retirer les caractères dangereux
        $fileName = str_replace(['..', '/', '\\', "\0"], '', $fileName);
        
        // Garder seulement les caractères alphanumériques, points, tirets et underscores
        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
        
        // Limiter la longueur
        if (strlen($fileName) > 255) {
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $basename = substr(pathinfo($fileName, PATHINFO_FILENAME), 0, 200);
            $fileName = $basename . '.' . $extension;
        }

        return $fileName;
    }

    /**
     * Get uploaded file (helper pour récupérer le fichier dans Livewire)
     */
    public function getFile(string $uuid)
    {
        $disk = config('livewire.temporary_file_upload.disk') ?? 'local';
        $path = "livewire-tmp/{$uuid}";
        $metaPath = "{$path}.meta";

        if (!Storage::disk($disk)->exists($path) || !Storage::disk($disk)->exists($metaPath)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found or expired',
            ], 404);
        }

        $metadata = json_decode(Storage::disk($disk)->get($metaPath), true);

        return response()->json([
            'success' => true,
            'data' => $metadata,
        ]);
    }
}
