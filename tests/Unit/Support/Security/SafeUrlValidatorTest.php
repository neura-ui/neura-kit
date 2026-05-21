<?php

namespace Neura\Kit\Tests\Unit\Support\Security;

use InvalidArgumentException;
use Neura\Kit\Support\Security\SafeUrlValidator;
use Neura\Kit\Tests\TestCase;

class SafeUrlValidatorTest extends TestCase
{
    private SafeUrlValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new SafeUrlValidator;
    }

    /** @test */
    public function it_allows_public_https_urls(): void
    {
        $this->validator->assertFetchable('https://example.com/image.jpg');

        $this->assertTrue($this->validator->isSafeHref('https://example.com/path'));
    }

    /** @test */
    public function it_blocks_localhost(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->assertFetchable('http://localhost/admin');
    }

    /** @test */
    public function it_blocks_private_ip_literals(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->assertFetchable('http://127.0.0.1/');
    }

    /** @test */
    public function it_blocks_javascript_hrefs(): void
    {
        $this->assertFalse($this->validator->isSafeHref('javascript:alert(1)'));
    }

    /** @test */
    public function it_blocks_file_scheme(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->assertFetchable('file:///etc/passwd');
    }
}
