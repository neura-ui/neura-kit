<?php

namespace Neura\Kit\Tests\Unit\Support\Security;

use Illuminate\Http\UploadedFile;
use Neura\Kit\Support\Security\UploadMimeValidator;
use Neura\Kit\Tests\TestCase;
use RuntimeException;

class UploadMimeValidatorTest extends TestCase
{
    private UploadMimeValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new UploadMimeValidator;
        config(['neura-kit.upload.allowed_mimes' => null]);
    }

    /** @test */
    public function it_skips_validation_when_allowlist_is_null(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 10, 'application/pdf');

        $this->validator->assertAllowed($file);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_enforces_configured_mime_allowlist(): void
    {
        config(['neura-kit.upload.allowed_mimes' => ['image/jpeg', 'image/png']]);

        $allowed = UploadedFile::fake()->image('photo.jpg');
        $this->validator->assertAllowed($allowed);

        $denied = UploadedFile::fake()->create('script.pdf', 10, 'application/pdf');

        $this->expectException(RuntimeException::class);
        $this->validator->assertAllowed($denied);
    }

    /** @test */
    public function it_supports_wildcard_mime_groups(): void
    {
        config(['neura-kit.upload.allowed_mimes' => ['image/*']]);

        $file = UploadedFile::fake()->image('photo.png');

        $this->validator->assertAllowed($file);

        $this->assertSame('image/png', $file->getMimeType());
    }
}
