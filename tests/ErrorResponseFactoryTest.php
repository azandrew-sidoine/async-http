<?php

use Drewlabs\Async\Http\Contracts\ResponseInterface;
use PHPUnit\Framework\TestCase;

use function Drewlabs\Async\Http\createResponse;
use function Drewlabs\Async\Http\errorResponse;

class ErrorResponseFactoryTest extends TestCase
{

    public function test_create_error_response_creates_an_exception_instance()
    {
        $error = errorResponse();
        $this->assertInstanceOf(\Exception::class, $error);
    }

    public function test_error_response_has_response_returns_false_if_no_response_is_provided()
    {
        $error = errorResponse();
        $this->assertFalse($error->hasResponse());
    }

    public function test_error_response_returns_true_if_response_is_provided()
    {
        $exception = errorResponse(createResponse('Not Found!', 404));
        $this->assertTrue($exception->hasResponse());
    }

    public function test_error_response_get_response_returns_the_provided_response()
    {
        $response = createResponse('Not Found!', 404);
        $exception = errorResponse($response);
        $this->assertInstanceOf(ResponseInterface::class, $exception->getResponse());
        $this->assertEquals(404, $exception->getResponse()->getStatusCode());
    }
}