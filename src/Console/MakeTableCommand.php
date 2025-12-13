<?php

namespace Neura\Kit\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

class MakeTableCommand extends Command
{
    protected $signature = 'neura-kit:make-table {name : The name of the table component}';

    protected $description = 'Create a new Neura Kit table component';

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

        if ($this->files->exists($classPath)) {
            $this->error('Component class already exists!');
            return self::FAILURE;
        }

        $this->makeDirectory($classPath);

        $this->files->put($classPath, $this->buildClass($className));

        $this->info('Table component created successfully.');
        $this->line("Class: {$classPath}");
        $this->line("Note: The table view is automatically handled by the base Table component.");

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

    protected function buildClass(string $name): string
    {
        $namespace = Str::beforeLast($name, '\\');
        $class = Str::afterLast($name, '\\');

        return $this->resolveStub('Table.stub', [
            'namespace' => $namespace,
            'class' => $class,
        ]);
    }

    protected function resolveStub(string $stub, array $replacements = []): string
    {
        // 1. Look for published stub in project root
        $customPath = base_path("stubs/neura-kit/livewire/table/{$stub}");
        
        // 2. Look for package stub
        $packagePath = __DIR__ . "/../../stubs/livewire/table/{$stub}";

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

