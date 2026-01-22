<?php

namespace Tests\Unit\Services;

use Illuminate\Support\Facades\Session;
use Neura\Kit\Services\ToastService;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ToastServiceTest extends TestCase
{
    protected ToastService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ToastService;
    }

    #[Test]
    public function it_flashes_toast_to_session_when_not_in_livewire()
    {
        $this->service->success('Hello World');

        $this->assertTrue(Session::has('notify'));
        $notification = Session::get('notify');

        $this->assertEquals('Hello World', $notification['content']);
        $this->assertEquals('success', $notification['type']);
        $this->assertEquals(4000, $notification['duration']);
    }

    #[Test]
    public function it_does_nothing_if_content_is_empty()
    {
        $this->service->flash('');
        $this->assertFalse(Session::has('notify'));
    }

    #[Test]
    public function it_helper_methods_work()
    {
        $this->service->error('Error occurred');

        $notification = Session::get('notify');
        $this->assertEquals('error', $notification['type']);
    }
}
