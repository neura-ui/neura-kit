<?php

namespace Neura\Kit\Tests\Unit\Services\Upload;

use Neura\Kit\Services\Upload\FileNameSanitizerService;
use Neura\Kit\Tests\TestCase;

class FileNameSanitizerServiceTest extends TestCase
{
    private FileNameSanitizerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FileNameSanitizerService();
    }

    /** @test */
    public function it_removes_directory_traversal_attempts()
    {
        $result = $this->service->sanitize('../../../etc/passwd');
        
        $this->assertStringNotContainsString('..', $result);
        $this->assertStringNotContainsString('/', $result);
    }

    /** @test */
    public function it_removes_null_bytes()
    {
        $result = $this->service->sanitize("file\0name.txt");
        
        $this->assertStringNotContainsString("\0", $result);
    }

    /** @test */
    public function it_removes_backslashes()
    {
        $result = $this->service->sanitize('path\\to\\file.txt');
        
        $this->assertStringNotContainsString('\\', $result);
    }

    /** @test */
    public function it_replaces_special_characters_with_underscores()
    {
        $result = $this->service->sanitize('file name with spaces & special!@#.txt');
        
        $this->assertStringContainsString('_', $result);
        $this->assertStringNotContainsString(' ', $result);
        $this->assertStringNotContainsString('&', $result);
        $this->assertStringNotContainsString('!', $result);
    }

    /** @test */
    public function it_preserves_valid_characters()
    {
        $result = $this->service->sanitize('valid-file_name.123.txt');
        
        $this->assertEquals('valid-file_name.123.txt', $result);
    }

    /** @test */
    public function it_truncates_long_filenames()
    {
        $longName = str_repeat('a', 300) . '.txt';
        
        $result = $this->service->sanitize($longName);
        
        $this->assertLessThanOrEqual(255, strlen($result));
        $this->assertStringEndsWith('.txt', $result);
    }

    /** @test */
    public function it_handles_empty_filename()
    {
        $result = $this->service->sanitize('');
        
        $this->assertNotEmpty($result);
        $this->assertStringStartsWith('file_', $result);
    }

    /** @test */
    public function it_handles_filename_with_only_dots()
    {
        $result = $this->service->sanitize('...');
        
        $this->assertNotEquals('.', $result);
        $this->assertNotEmpty($result);
    }

    /** @test */
    public function it_preserves_file_extension()
    {
        $result = $this->service->sanitize('my file.jpg');
        
        $this->assertStringEndsWith('.jpg', $result);
    }

    /** @test */
    public function it_handles_multiple_extensions()
    {
        $result = $this->service->sanitize('archive.tar.gz');
        
        $this->assertStringContainsString('tar.gz', $result);
    }
}
