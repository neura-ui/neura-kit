<?php

namespace Neura\Kit\Tests\Unit\Services;

use Neura\Kit\Services\NeuraKitService;
use Neura\Kit\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class NeuraKitServiceTest extends TestCase
{
    protected NeuraKitService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NeuraKitService;
    }

    #[Test]
    public function it_returns_empty_string_when_no_component_provided()
    {
        $result = $this->service->openModal([]);
        $this->assertEquals('', $result);
    }

    #[Test]
    public function it_generates_correct_js_call_for_modal()
    {
        $params = [
            'component' => 'user-edit',
            'arguments' => ['id' => 1],
            'modalAttributes' => ['size' => 'lg'],
        ];

        $result = $this->service->openModal($params);

        $this->assertStringContainsString('NeuraKitModal.open', $result);
        $this->assertStringContainsString('&quot;user-edit&quot;', $result); // json encoded component
        $this->assertStringContainsString('{&quot;id&quot;:1}', $result); // json encoded args
        $this->assertStringContainsString('{&quot;size&quot;:&quot;lg&quot;}', $result); // json encoded attributes
    }
}
