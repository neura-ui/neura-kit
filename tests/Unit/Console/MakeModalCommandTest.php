<?php

namespace Neura\Kit\Tests\Unit\Console;

use Illuminate\Filesystem\Filesystem;
use Mockery;
use Neura\Kit\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class MakeModalCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_creates_modal_files()
    {
        $files = Mockery::mock(Filesystem::class);

        // Checks if files exist (should return false to proceed)
        $files->shouldReceive('exists')
            ->times(2)
            ->andReturn(false);

        // Checks if directories exist
        $files->shouldReceive('isDirectory')
            ->andReturn(true);

        // Expects file creation
        $files->shouldReceive('put')
            ->with(
                Mockery::on(fn ($path) => str_contains($path, 'TestModal.php')),
                Mockery::on(fn ($content) => str_contains($content, 'class TestModal extends ModalComponent'))
            )
            ->once();

        $files->shouldReceive('put')
            ->with(
                Mockery::on(fn ($path) => str_contains($path, 'test-modal.blade.php')),
                Mockery::on(fn ($content) => str_contains($content, '<neura::modal.header'))
            )
            ->once();

        $this->app->instance(Filesystem::class, $files);

        $this->artisan('neura-kit:make-modal', ['name' => 'TestModal'])
            ->assertExitCode(0)
            ->expectsOutput('Modal component created successfully.');
    }
}
