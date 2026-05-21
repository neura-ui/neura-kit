<?php

namespace Neura\Kit\Support\Security;

use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Enforces configurable MIME allowlists for uploads.
 */
class UploadMimeValidator
{
    /**
     * @throws RuntimeException
     */
    public function assertAllowed(UploadedFile|string $file): void
    {
        $allowed = config('neura-kit.upload.allowed_mimes');

        if ($allowed === null || $allowed === []) {
            return;
        }

        $mime = $this->resolveMime($file);

        if ($mime === null) {
            throw new RuntimeException('Unable to determine file MIME type.');
        }

        if (! $this->mimeMatchesAllowlist($mime, $allowed)) {
            throw new RuntimeException('File type is not allowed.');
        }
    }

    protected function resolveMime(UploadedFile|string $file): ?string
    {
        if ($file instanceof UploadedFile) {
            return $file->getMimeType() ?: null;
        }

        if (! is_file($file)) {
            return null;
        }

        $detected = mime_content_type($file);

        return $detected !== false ? $detected : null;
    }

    /**
     * @param  list<string>  $allowed
     */
    protected function mimeMatchesAllowlist(string $mime, array $allowed): bool
    {
        $mime = strtolower($mime);

        foreach ($allowed as $rule) {
            $rule = strtolower(trim($rule));

            if ($rule === '' || $rule === '*/*') {
                return true;
            }

            if ($rule === $mime) {
                return true;
            }

            if (str_ends_with($rule, '/*')) {
                $prefix = rtrim($rule, '*');

                if (str_starts_with($mime, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }
}
