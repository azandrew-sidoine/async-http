# Async HTTP

The library provides an Async HTTP client for making HTTP request. It uses PHP `streams` API for send request to resource servers, there must only be used in environment that support PHP `streams` and `sockets` APIs.

## Usage

To send HTTP request using this library, follow the examples below:

```php
use function Drewlabs\Async\Future\await;
use function Drewlabs\Async\Http\createRequest;
use function Drewlabs\Async\Http\fetch;

// Creates the fetch client
$promise = fetch(createRequest('http://www.dneonline.com/calculator.asmx?wsdl', 'GET', null, []), ['debug' => true]); // Debugging should not be used in production

$response = await($promise);
printf("Content-Length: %d\n\r", strlen($response->getBody()));
```

If one is familiar which `async` syntax, one might do the following:

```php
use function Drewlabs\Async\Http\createRequest;
use function Drewlabs\Async\Http\fetch;

// Creates the fetch client
$promise = fetch(createRequest('http://www.dneonline.com/calculator.asmx?wsdl', 'GET', null, []), ['debug' => true]); // Debugging should not be used in production

// Provide then handlers
$promise->then(function($response) {
	printf($response->getBody());
	printf($response->getStatusCode());
});

// wait for request to complete response
$promise->wait();
```

## API

- createRequest `createRequest(string $url, string $method = 'GET', ?string $body = '', $headers = [])`
    This function is used to create a request instance compatible with the `fetch` interface

- createResponse `createResponse(string $body = '', int $statusCode = 200, array $headers = [], string $reasonPhrase = 'OK')`
    Creates a response instance compatible with one returned by the `fetch` interface

- fetch `fetch(RequestInterface $request): ResponseInterface` `fetch(RequestInterface[] $requests): ResponseInterface[]` `fetch(string $url): ResponseInterface`
    HTTP client for sending HTTP request asynchronously.