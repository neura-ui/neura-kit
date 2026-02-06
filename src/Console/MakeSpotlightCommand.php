<?php

declare(strict_types=1);

namespace Neura\Kit\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:spotlight')]
class MakeSpotlightCommand extends GeneratorCommand
{
    protected $name = 'make:spotlight';

    protected $description = 'Create a new Spotlight command class';

    protected function type(): string
    {
        $type = $this->option('type') ?? 'action';

        return match ($type) {
            'search' => 'Spotlight Search Provider',
            'ai' => 'Spotlight AI Provider',
            default => 'Spotlight Command',
        };
    }

    protected function getStub(): string
    {
        $type = $this->option('type') ?? 'action';

        $stubName = match ($type) {
            'search' => 'search-provider.stub',
            'ai' => 'ai-provider.stub',
            default => 'command.stub',
        };

        return __DIR__.'/../../stubs/Spotlight/'.$stubName;
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        $type = $this->option('type') ?? 'action';

        return match ($type) {
            'search' => $rootNamespace.'\\Spotlight\\Search',
            'ai' => $rootNamespace.'\\Spotlight\\Ai',
            default => $rootNamespace.'\\Spotlight\\Commands',
        };
    }

    protected function buildClass($name): string
    {
        $stub = parent::buildClass($name);
        $type = $this->option('type') ?? 'action';

        // Replace custom placeholders
        $commandName = str($this->getNameInput())
            ->replace('Command', '')
            ->replace('Provider', '')
            ->headline()
            ->toString();

        $commandId = str($this->getNameInput())
            ->replace('Command', '')
            ->replace('Provider', '')
            ->kebab()
            ->toString();

        $stub = str_replace('{{ commandName }}', $commandName, $stub);
        $stub = str_replace('{{ commandId }}', $commandId, $stub);

        // Handle type-specific execute method for commands only
        if (in_array($type, ['action', 'navigation'])) {
            return match ($type) {
                'navigation' => $this->buildNavigationCommand($stub),
                default => str_replace('{{ executeMethod }}', '// TODO: Implement your action logic here', $stub),
            };
        }

        return $stub;
    }

    protected function buildNavigationCommand(string $stub): string
    {
        return str_replace(
            '{{ executeMethod }}',
            "return \$this->goTo('/');",
            $stub
        );
    }

    protected function getOptions(): array
    {
        return [
            ['type', 't', InputOption::VALUE_OPTIONAL, 'The type of command (action, navigation, search, ai)', 'action'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the command already exists'],
        ];
    }
}
