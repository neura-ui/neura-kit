<?php

namespace Neura\Kit\Support\Map;

use RuntimeException;

/**
 * A FeatureCollection written one feature at a time, into a temporary file that takes its
 * place only once complete, so a reader never sees half a file.
 */
final class FeatureWriter
{
    /** @var resource */
    private $handle;

    private readonly string $temporary;

    private bool $first = true;

    public function __construct(private readonly string $path)
    {
        $directory = dirname($path);

        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException("Cannot create {$directory}.");
        }

        $this->temporary = $path.'.'.getmypid().'.tmp';
        $handle = @fopen($this->temporary, 'wb');

        if ($handle === false) {
            throw new RuntimeException("Cannot write {$this->temporary}.");
        }

        $this->handle = $handle;
        fwrite($this->handle, '{"type":"FeatureCollection","features":[');
    }

    /** @param array<string, mixed> $feature */
    public function add(array $feature): void
    {
        fwrite($this->handle, ($this->first ? '' : ',').json_encode($feature, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->first = false;
    }

    public function close(): void
    {
        fwrite($this->handle, ']}');
        fclose($this->handle);

        if (! @rename($this->temporary, $this->path)) {
            @unlink($this->temporary);

            throw new RuntimeException("Cannot move {$this->temporary} to {$this->path}.");
        }
    }
}
