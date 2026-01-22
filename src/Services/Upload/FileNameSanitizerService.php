<?php

namespace Neura\Kit\Services\Upload;

/**
 * Service for sanitizing filenames to prevent security issues
 */
class FileNameSanitizerService
{
    /**
     * Sanitize filename to prevent directory traversal and other attacks
     */
    public function sanitize(string $fileName): string
    {
        // Remove dangerous characters
        $fileName = str_replace(['..', '/', '\\', "\0"], '', $fileName);

        // Keep only alphanumeric characters, dots, hyphens and underscores
        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);

        // Limit length
        if (strlen($fileName) > 255) {
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $basename = substr(pathinfo($fileName, PATHINFO_FILENAME), 0, 200);
            $fileName = $basename . '.' . $extension;
        }

        // Ensure filename is not empty
        if (empty($fileName) || $fileName === '.') {
            $fileName = 'file_' . uniqid();
        }

        return $fileName;
    }
}
