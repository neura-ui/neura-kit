<?php

declare(strict_types=1);

namespace Neura\Kit\Tests\Unit\Support;

use Neura\Kit\Support\Canonicalizer;
use Neura\Kit\Tests\TestCase;

class CanonicalizerTest extends TestCase
{
    public function test_canonicalizes_simple_array(): void
    {
        $data = [
            'z' => 3,
            'a' => 1,
            'm' => 2,
        ];

        $result = Canonicalizer::canonicalize($data);

        $this->assertIsArray($result);
        $this->assertEquals(['a' => 1, 'm' => 2, 'z' => 3], $result);
        $this->assertArrayHasKey('a', $result);
        $this->assertArrayHasKey('m', $result);
        $this->assertArrayHasKey('z', $result);
    }

    public function test_canonicalizes_nested_arrays(): void
    {
        $data = [
            'z' => [
                'c' => 3,
                'a' => 1,
            ],
            'a' => 2,
        ];

        $result = Canonicalizer::canonicalize($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('a', $result);
        $this->assertArrayHasKey('z', $result);
        $this->assertIsArray($result['z']);
        $this->assertEquals(['a' => 1, 'c' => 3], $result['z']);
    }

    public function test_is_deterministic(): void
    {
        $data = [
            'license_key' => 'test-key',
            'plan' => 'solo',
            'features' => ['all', 'advanced'],
        ];

        $result1 = Canonicalizer::canonicalize($data);
        $result2 = Canonicalizer::canonicalize($data);

        $this->assertEquals($result1, $result2);
        $this->assertIsArray($result1);
        $this->assertIsArray($result2);
    }

    public function test_preserves_values(): void
    {
        $data = [
            'string' => 'test',
            'integer' => 123,
            'boolean' => true,
            'null' => null,
        ];

        $result = Canonicalizer::canonicalize($data);

        $this->assertIsArray($result);
        $this->assertEquals('test', $result['string']);
        $this->assertEquals(123, $result['integer']);
        $this->assertTrue($result['boolean']);
        $this->assertNull($result['null']);
    }
}
