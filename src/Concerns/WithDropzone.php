<?php

namespace Neura\Kit\Concerns;

use Neura\Kit\Support\ChunkedTemporaryFile;
use Neura\Kit\Support\Dropzone\DropzoneFiles;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Trait pour les composants Livewire utilisant le dropzone
 * 
 * Fournit des méthodes helper pour convertir les données du dropzone
 * en TemporaryUploadedFile utilisables directement.
 * 
 * Usage:
 * ```php
 * class MyComponent extends Component
 * {
 *     use WithDropzone;
 *     
 *     public $documents = [];
 *     
 *     public function save()
 *     {
 *         // Obtenir les fichiers en tant que TemporaryUploadedFile
 *         $files = $this->getDropzoneFiles('documents');
 *         
 *         foreach ($files as $file) {
 *             $file->store('documents');
 *         }
 *         
 *         // Ou stocker directement
 *         $paths = $this->storeDropzoneFiles('documents', 'uploads/documents');
 *     }
 * }
 * ```
 */
trait WithDropzone
{
    /**
     * Obtenir les fichiers du dropzone en tant que TemporaryUploadedFile
     * 
     * @param string $property Nom de la propriété Livewire
     * @return DropzoneFiles Collection de TemporaryUploadedFile
     */
    public function getDropzoneFiles(string $property): DropzoneFiles
    {
        $data = $this->{$property} ?? [];
        return DropzoneFiles::from($data);
    }
    
    /**
     * Obtenir un seul fichier du dropzone (pour les uploads non-multiples)
     * 
     * @param string $property Nom de la propriété Livewire
     * @return TemporaryUploadedFile|null
     */
    public function getDropzoneFile(string $property): ?TemporaryUploadedFile
    {
        $data = $this->{$property} ?? null;
        
        if (empty($data)) {
            return null;
        }
        
        // Si c'est déjà un TemporaryUploadedFile
        if ($data instanceof TemporaryUploadedFile) {
            return $data;
        }
        
        // Si c'est un array avec uuid
        if (is_array($data) && isset($data['uuid'])) {
            return ChunkedTemporaryFile::fromDropzone($data);
        }
        
        // Si c'est un array de fichiers, prendre le premier
        if (is_array($data) && isset($data[0])) {
            return ChunkedTemporaryFile::fromDropzone($data[0]);
        }
        
        return null;
    }
    
    /**
     * Stocker tous les fichiers du dropzone
     * 
     * @param string $property Nom de la propriété Livewire
     * @param string $path Chemin de stockage
     * @param string|null $disk Disque de stockage (null = default)
     * @return array Chemins des fichiers stockés
     */
    public function storeDropzoneFiles(string $property, string $path, ?string $disk = null): array
    {
        return $this->getDropzoneFiles($property)->storeAll($path, $disk);
    }
    
    /**
     * Stocker un seul fichier du dropzone
     * 
     * @param string $property Nom de la propriété Livewire
     * @param string $path Chemin de stockage
     * @param string|null $disk Disque de stockage (null = default)
     * @return string|null Chemin du fichier stocké
     */
    public function storeDropzoneFile(string $property, string $path, ?string $disk = null): ?string
    {
        $file = $this->getDropzoneFile($property);
        
        if (!$file) {
            return null;
        }
        
        return $file->store($path, $disk ?? config('filesystems.default'));
    }
    
    /**
     * Vider les fichiers du dropzone après traitement
     * 
     * @param string $property Nom de la propriété Livewire
     * @return void
     */
    public function clearDropzone(string $property): void
    {
        $this->{$property} = [];
    }
}
