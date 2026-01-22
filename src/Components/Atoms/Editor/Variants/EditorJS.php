<?php

namespace Neura\Kit\Components\Atoms\Editor\Variants;

use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class EditorJS extends Component
{
    use WithFileUploads;

    public array $uploads = [];

    public string $editorId;

    public ?array $data = null;

    public string $class = '';

    public string $style = '';

    public bool $readOnly = false;

    public string $placeholder = 'Commencez à écrire...';

    public ?string $uploadDisk = 'public';

    public ?string $downloadDisk = 'public';

    public ?string $imagesPath = 'editor-images';

    public ?string $logLevel = 'error';

    public string $dataProperty = 'data';

    public function mount(
        string $editorId,
        ?array $value = null,
        string $dataProperty = 'data',
        string $class = '',
        string $style = '',
        bool $readOnly = false,
        ?string $placeholder = null,
        ?string $uploadDisk = null,
        ?string $downloadDisk = null,
        ?string $imagesPath = null,
        ?string $logLevel = null
    ): void {
        $this->editorId = $editorId;
        $this->dataProperty = $dataProperty;
        $this->data = $value;
        $this->class = $class;
        $this->style = $style;
        $this->readOnly = $readOnly;
        $this->placeholder = $placeholder ?? config('neura-kit.rich_editor.placeholder', 'Commencez à écrire...');
        $this->uploadDisk = $uploadDisk ?? config('neura-kit.rich_editor.upload_disk', 'public');
        $this->downloadDisk = $downloadDisk ?? config('neura-kit.rich_editor.download_disk', 'public');
        $this->imagesPath = $imagesPath ?? config('neura-kit.rich_editor.images_path', 'editor-images');
        $this->logLevel = $logLevel ?? config('neura-kit.rich_editor.log_level', 'error');
    }

    public function completedImageUpload(string $uploadedFileName): array
    {
        /** @var TemporaryUploadedFile|null $tmpFile */
        $tmpFile = collect($this->uploads)
            ->filter(fn (TemporaryUploadedFile $item) => $item->getFilename() === $uploadedFileName)
            ->first();

        if (!$tmpFile) {
            return [
                'success' => 0,
                'file' => ['url' => ''],
            ];
        }

        $uploadDisk = $this->uploadDisk ?? 'public';
        $imagesPath = $this->imagesPath ?? 'editor-images';

        $storedFileName = $tmpFile->storeAs(
            '/'.$imagesPath,
            $tmpFile->hashName(),
            $uploadDisk
        );

        $url = Storage::disk($uploadDisk)->url($storedFileName);

        return [
            'success' => 1,
            'file' => [
                'url' => $url,
            ],
        ];
    }

    public function loadImageFromUrl(string $url): string
    {
        $name = basename(parse_url($url, PHP_URL_PATH));

        if (empty($name) || !preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $name)) {
            $name = 'image_'.time().'.jpg';
        }

        try {
            $content = file_get_contents($url);

            if ($content === false) {
                return $url;
            }

            $downloadDisk = $this->downloadDisk ?? 'public';
            $imagesPath = $this->imagesPath ?? 'editor-images';

            Storage::disk($downloadDisk)->put($imagesPath.'/'.$name, $content);

            return Storage::disk($downloadDisk)->url($imagesPath.'/'.$name);
        } catch (\Exception $e) {
            return $url;
        }
    }

    public function save(): void
    {
        $this->dispatch('editorjs-saved', [
            'editorId' => $this->editorId,
            'data' => $this->data,
        ]);
    }

    public function render()
    {
        return view('neura::editor.variants.editorjs.index', [
            'editorId' => $this->editorId,
            'dataProperty' => $this->dataProperty,
            'readOnly' => $this->readOnly,
            'placeholder' => $this->placeholder,
            'logLevel' => $this->logLevel,
            'class' => $this->class,
            'style' => $this->style,
            'data' => $this->data,
        ]);
    }
}
