<?php

namespace Neura\Kit\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

class MakeModalCommand extends Command
{
    protected $signature = 'neura-kit:make-modal {name : The name of the modal component}';

    protected $description = 'Create a new Neura Kit modal component';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        
        $className = $this->qualifyClass($name);
        $classPath = $this->getPath($className);
        
        $viewName = Str::kebab($name);
        $viewDir = config('livewire.view_path') ?: resource_path('views/livewire');
        $viewPath = $viewDir . '/' . $viewName . '.blade.php';

        if ($this->files->exists($classPath)) {
            $this->error('Component class already exists!');
            return self::FAILURE;
        }

        if ($this->files->exists($viewPath)) {
            $this->error('Component view already exists!');
            return self::FAILURE;
        }

        $this->makeDirectory($classPath);
        $this->makeDirectory($viewPath);

        $this->files->put($classPath, $this->buildClass($className, $viewName));
        $this->files->put($viewPath, $this->buildView($name));

        $this->info('Modal component created successfully.');
        $this->line("Class: {$classPath}");
        $this->line("View: {$viewPath}");

        return self::SUCCESS;
    }

    protected function qualifyClass(string $name): string
    {
        $name = ltrim($name, '\\/');
        $rootNamespace = config('livewire.class_namespace', 'App\\Livewire');

        if (Str::startsWith($name, $rootNamespace)) {
            return $name;
        }

        return $rootNamespace . '\\' . str_replace('/', '\\', $name);
    }

    protected function getPath(string $name): string
    {
        $name = Str::replaceFirst($this->laravel->getNamespace(), '', $name);

        return $this->laravel['path'] . '/' . str_replace('\\', '/', $name) . '.php';
    }

    protected function makeDirectory(string $path): void
    {
        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }

    protected function buildClass(string $name, string $viewName): string
    {
        $namespace = Str::beforeLast($name, '\\');
        $class = Str::afterLast($name, '\\');
        
        $viewDir = config('livewire.view_path') ?: resource_path('views/livewire');
        $relativePath = Str::after($viewDir, resource_path('views'));
        $relativePath = ltrim($relativePath, '/\\');
        
        $viewPrefix = str_replace(['/', '\\'], '.', $relativePath);
        if ($viewPrefix && !str_ends_with($viewPrefix, '.')) {
            $viewPrefix .= '.';
        }
        
        $view = $viewPrefix . $viewName;

        return $this->resolveStub('Modal.stub', [
            'namespace' => $namespace,
            'class' => $class,
            'view' => $view,
        ]);
    }

    protected function buildView(string $name): string
    {
        $title = Str::headline($name);

        return $this->resolveStub('modal.blade.stub', [
            'title' => $title,
        ]);
    }

    protected function resolveStub(string $stub, array $replacements = []): string
    {
        // 1. Look for published stub in project root
        $customPath = base_path("stubs/neura-kit/livewire/modal/{$stub}");
        
        // 2. Look for package stub
        $packagePath = __DIR__ . "/../../stubs/livewire/modal/{$stub}";

        $path = file_exists($customPath) ? $customPath : $packagePath;

        if (! file_exists($path)) {
            // Fallback content if stub file is missing (failsafe)
            return '';
        }

        $content = file_get_contents($path);

        foreach ($replacements as $key => $value) {
            $content = str_replace("[$key]", $value, $content);
        }

        return $content;
    }
}
