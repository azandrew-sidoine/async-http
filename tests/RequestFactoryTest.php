<?php

use Drewlabs\Async\Http\Contracts\RequestInterface;
use PHPUnit\Framework\TestCase;

use function Drewlabs\Async\Http\createRequest;

class RequestFactoryTest extends TestCase
{

    public function test_create_request_creates_a_request_interface_instance()
    {
        $request = createRequest('http://127.0.0.1:8000');

        $this->assertInstanceOf(RequestInterface::class, $request);
    }

    public function test_create_request_create_a_get_request_by_default()
    {
        $request = createRequest('http://127.0.0.1:8000');
        $this->assertEquals('GET', $request->getMethod());
    }

    public function test_create_request_create_a_request_with_the_provided_method()
    {
        $request = createRequest('http://127.0.0.1:8000', 'POST');
        $this->assertEquals('POST', $request->getMethod());
    }

    public function test_request_get_header_is_case_insensitive()
    {
        $request = createRequest('http://127.0.0.1:8000', 'OPTIONS', '', ['Content-Type' => 'application/json', 'Origin' => 'http://localhost']);

        $this->assertEquals($request->getHeader('origin'), 'http://localhost');
        $this->assertEquals($request->getHeader('content-type'), 'application/json');
    }
}