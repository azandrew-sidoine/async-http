<?php

use Drewlabs\Async\Http\Contracts\ResponseInterface;
use PHPUnit\Framework\TestCase;

use function Drewlabs\Async\Http\createResponse;

class ResponseFactoryTest extends TestCase
{

    public function test_create_response_creates_a_response_interface_instance()
    {
        $response = createResponse('http://127.0.0.1:8000');

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function test_create_response_provides_default_case_no_parameter_is_provided()
    {
        $response = createResponse();
        $this->assertEquals('', $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertEquals('OK', $response->getReasonPhrase());
    }

    public function test_create_response_create_a_response_with_the_provided_body_and_status_code()
    {
        $body = json_encode(['errors' => ['password' => ['password is required']]]);
        $response = createResponse($body, 422);
        $this->assertEquals($body, $response->getBody());
        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_response_get_header_is_case_insensitive()
    {
        $response = createResponse('', 200, ['Content-Type' => 'application/json', 'Origin' => 'http://localhost']);

        $this->assertEquals($response->getHeader('origin'), 'http://localhost');
        $this->assertEquals($response->getHeader('content-type'), 'application/json');
    }

}