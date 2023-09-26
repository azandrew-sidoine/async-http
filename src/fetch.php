<?php

namespace Drewlabs\Async\Http;

use Drewlabs\Async\Http\Contracts\RequestException;
use Drewlabs\Async\Http\Contracts\RequestInterface;
use Drewlabs\Async\Http\Contracts\ResponseInterface;
use Drewlabs\Async\Http\Traits\Message;
use Drewlabs\Async\PromiseInterface;
use Drewlabs\Async\Awaitable;
use function Drewlabs\Async\Future\async;
use function Drewlabs\Async\Future\join;
use function Drewlabs\Async\IO\createSocket;

/**
 * Creates a new `ResponseInterface` instance
 * 
 * @param string $body 
 * @param int $statusCode 
 * @param array $headers 
 * @param string $reasonPhrase 
 * @return ResponseInterface 
 */
function createResponse(string $body = '', int $statusCode = 200, array $headers = [], string $reasonPhrase = 'OK')
{
    return new class($body, $statusCode, $headers, $reasonPhrase) implements ResponseInterface
    {
        use Message;

        /**
         * @var int
         */
        private $statusCode;

        /**
         * @var string
         */
        private $reasonPhrase;

        /**
         * response constructor
         * 
         * @param string $body 
         * @param int $statusCode 
         * @param array $headers 
         * @param string $reasonPhrase 
         */
        public function __construct(string $body = '', int $statusCode = 200, array $headers = [], string $reasonPhrase = '')
        {
            $this->body = $body ?? '';
            $this->statusCode = $statusCode;
            $this->headers = $headers ?? [];
            $this->reasonPhrase = $reasonPhrase;
        }

        public function getStatusCode()
        {
            return $this->statusCode;
        }

        public function getReasonPhrase()
        {
            return $this->reasonPhrase;
        }
    };
}

/**
 * Create a new instance of `RequestInterface`
 * 
 * @param string $url 
 * @param string $method 
 * @param null|string $body 
 * @param array $headers 
 * @return RequestInterface 
 */
function createRequest(string $url, string $method = 'GET', ?string $body = '', $headers = [])
{

    return new class($url, $method, $body, $headers) implements RequestInterface
    {
        use Message;

        /**
         * @var string
         */
        private $url;

        /**
         * @var string
         */
        private $method;

        /**
         * Creates new request instance
         * 
         * @param string $url 
         * @param string $method 
         * @param null|string $body 
         * @param array $headers 
         */
        public function __construct(string $url, string $method = 'GET', ?string $body = '', $headers = [])
        {
            $this->url = $url;
            $this->body = $body ?? '';
            $this->method = $method ?? 'GET';
            $this->headers = $headers ?? [];
        }

        public function getMethod()
        {
            return strtoupper($this->method ?? 'GET');
        }

        public function getUrl()
        {
            return $this->url;
        }
    };
}

/**
 * Creates a request instance that encode it body using json encoder
 * @param string $url 
 * @param string $method 
 * @param string|array|object $body 
 * @param array $headers 
 * @return RequestInterface 
 */
function createJsonRequest(string $url, $method = 'GET', $body = '', array $headers = [])
{
    return createRequest($url, $method, is_string($body) ? $body : json_encode($body), array_merge($headers ?? [], ['Content-Type' => 'application/json']));
}

/**
 * Create error response instance
 * 
 * @param ResponseInterface $response 
 * @param string|null $message 
 * @param int $code 
 * @return RequestException 
 */
function errorResponse(ResponseInterface $response = null, string $message = null, int $code = 500)
{
    return new class($response, $message, $code) extends \Exception implements RequestException
    {
        /**
         * @var ResponseInterface
         */
        private $response;

        /**
         * Creates new request exception instance
         * 
         * @param ResponseInterface $response 
         * @param string|null $message 
         * @param int $code 
         */
        public function __construct(ResponseInterface $response = null, string $message = null, int $code = 500)
        {
            parent::__construct($message, $code);
            $this->response = $response;
        }

        public function hasResponse()
        {
            return null !== $this->getResponse();
        }

        public function getResponse()
        {
            return $this->response;
        }
    };
}

/**
 * Parse response header string
 * 
 * @param mixed $list 
 * @return array 
 */
function parseHeaders($list)
{
    $list = preg_split('/\r\n/', (string) ($list ?? ''), -1, \PREG_SPLIT_NO_EMPTY);
    $httpHeaders = [];
    $httpHeaders['Request-Line'] = reset($list) ?? '';
    for ($i = 1; $i < \count($list); ++$i) {
        if (str_contains($list[$i], ':')) {
            [$key, $value] = array_map(static function ($item) {
                return $item ? trim($item) : null;
            }, explode(':', $list[$i], 2));
            $httpHeaders[$key] = $value;
        }
    }

    return $httpHeaders;
}

/**
 * Get request header caseless.
 *
 * @return string
 */
function getHeader(array $headers, string $name)
{
    if (empty($headers)) {
        return null;
    }
    $normalized = strtolower($name);
    foreach ($headers as $key => $header) {
        if (strtolower($key) === $normalized) {
            return implode(',', \is_array($header) ? $header : [$header]);
        }
    }
    return null;
}

/**
 * Utility function to cleanup json string
 * 
 * @param string $string 
 * @return string 
 */
function cleanupJsonString(string $string)
{
    $start = strpos($string, '{');
    $end = strpos(strrev($string), '}');
    $strlen = strlen($string);
    if ($start && $end) {
        return substr($string, $start,  $strlen - ($start + strlen(substr($string, $end))));
    }
    return $string;
}

/**
 * Compute the dns address for the fetch request
 * @param string $url 
 * @param int|null $port 
 * @return string 
 * @throws InvalidArgumentException 
 */
function addressDSN(string $url, int $port = null)
{
    if (!preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $url)) {
        $scheme = parse_url($url, \PHP_URL_SCHEME);
        $protocol = $scheme === 'https' ? 'ssl' : ($scheme === 'http' ? 'tcp' : $scheme);
        /**
         * @var string
         */
        $host = parse_url($url, \PHP_URL_HOST);
        /**
         * @var string|int
         */
        $port = $port ?? parse_url($url, \PHP_URL_PORT) ?? ($scheme === 'https' ? 443 : ($scheme === 'http' ? 80 : null));
        if (!$host) {
            throw new \InvalidArgumentException('HOST URL is not a valid url component nor a valid address');
        }
        return sprintf("%s://%s", $protocol, sprintf("%s", sprintf("%s%s", $host, $port ? ":$port" : '')));
    }

    return sprintf("tcp://%s", sprintf("%s:%s", $url, 80));
}



/**
 * Internal response handler function
 * 
 * @param mixed $response 
 * @return ResponseInterface 
 */
function handleResponse($response)
{
    if (1 != preg_match("/^HTTP\/[0-9\.]* ([0-9]{3}) ([^\r\n]*)/", $response, $matches)) {
        throw errorResponse(null, 'Invalid HTTP reply.', 500);
    }
    $result = preg_split("/\r\n\r\n/Us", $response);
    $result = false === $result ? ['Bad Request', ''] : $result;
    $headers = parseHeaders($result[0] ?? '');
    $body = $result[1] ?? '';
    // Remove weird characters appearing before { and after } to make json body clean
    if (preg_match('/^(?:application|text)\/(?:[a-z]+(?:[\.-][0-9a-z]+){0,}[\+\.]|x-)?json(?:-[a-z]+)?/i', getHeader($headers, 'content-type'))) {
        $body = cleanupJsonString($body);
    }
    $statusCode = intval($matches[1]);
    if (200 > $statusCode || 201 < $statusCode) {
        return createResponse($body, $statusCode, $headers, $matches[2] ?? 'Bad Request');
    }
    // Reverse the splitted string for response body to come before the response headers
    return createResponse($body, $statusCode, $headers, $matches[2] ?? 'Ok');
}

/**
 * Internal function to prepare http request
 * 
 * @param RequestInterface $request 
 * @return string 
 */
function prepareHTTPRequest(RequestInterface $request, array $options = [])
{
    // #region Variables initialization
    $url = $request->getUrl();
    $headers = $request->getHeaders();
    $method = $request->getMethod();
    $options = $options ?? [];
    // #endregion Variables initialization

    $scheme = parse_url($url, \PHP_URL_SCHEME);
    $hostname = parse_url($url, \PHP_URL_HOST);
    $search = sprintf("%s://%s", $scheme, $hostname);
    $path = ltrim(str_replace($search, '', $url), '/');
    $body = '';
    $body .= strtoupper($method ?? 'GET') . " /$path HTTP/1.1\r\n";
    $body .= "Host: $hostname\r\n";
    if (is_string($requestBody = $request->getBody())) {
        $body .= 'Content-length: ' . strlen($requestBody) . "\r\n";
    }

    // Add req headers if any provided
    if (!empty($headers) && is_array($headers)) {
        foreach ($headers as $name => $value) {
            // TODO: RFC validate header name
            $body .= sprintf("%s: %s\r\n", $name, $value);
        }
    }

    // #region Add basic auth header if any
    if (isset($options['auth']['basic']) && !empty($options['auth']['basic']['username'])) {
        $auth = $options['auth']['basic'];
        $body .= "Authorization: Basic ";
        $body .= base64_encode($auth['username'] . ':' . $auth['password']) . "\r\n";
    }
    // #endregion Add basic auth header if any
    $body .= "Connection: close\r\n\r\n";

    if ($requestBody) {
        $body .= $requestBody;
        $body .= "\r\n";
    }
    return $body;
}


/**
 * Internal Generator or subroutine that handles HTTP request.
 * 
 * It uses PHP `stream_create_client` global to create a stream socket
 * to the web resource.
 * 
 * @param RequestInterface $request 
 * @param array $options
 * @return Generator<int, CoReturnValue<int|false>|void, mixed, Response> 
 * @throws InvalidArgumentException 
 * @throws Error 
 */
function request(RequestInterface $request, array $options = [])
{
    $options = $options ?? [];
    $address = addressDSN($request->getUrl());
    if (isset($options['debug']) && boolval($options['debug'])) {
        printf("Reading from: %s\n", $address);
    }
    // #region Prepare stream context
    $context = stream_context_create();
    stream_context_set_option($context, 'ssl', 'verify_host', true);
    if (!empty($options['cert'])) {
        stream_context_set_option($context, 'ssl', 'cafile', $options['cert']);
        stream_context_set_option($context, 'ssl', 'verify_peer', true);
    } else {
        stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
    }
    // #endregion Prepares stream context
    $fp = @stream_socket_client($address, $errno, $errstr, 60, STREAM_CLIENT_CONNECT, $context);
    if (!$fp) {
        throw errorResponse(null, $errstr);
    }
    stream_set_blocking($fp, false);

    // Create the async socket
    $socket = createSocket($fp);
    $req = prepareHTTPRequest($request, $options);
    if (isset($options['debug']) && boolval($options['debug'])) {
        printf("Request: \n%s\n", $req);
    }

    // Async write
    yield $socket->write($req);

    $content = '';
    while (true) {
        // Async read
        $bytes = (yield $socket->read(100));
        $content .= $bytes;
        if ($socket->eof()) {
            // Close the socket
            yield $socket->close();
            break;
        }
    }
    $response = handleResponse($content);
    return $response;
}

/**
 * 
 * `fetch` function creates a Promise A+ async instance to perform asynchrounous
 * HTTP request using PHP stream abstraction.
 * 
 * When an array of requests is passed as parameter, requests runs through
 * a lightweight coroutine pipeline that allow request to be run simultanously.
 * 
 * ```php
 * use function Drewlabs\Async\Http\createRequest;
 * 
 * fetch(createRequest('http://www.dneonline.com/calculator.asmx?wsdl', 'GET', null, []), ['debug' => true])(function (Response $response) {
 *    printf("Content-Length: %d\n", strlen($response->body));
 * }, function ($error) {
 *    printf("Request error: %s\n", $error);
 * });
 * ```
 * 
 * @param Request|Request[]|string $request
 * @param array $options 
 * @return PromiseInterface&Awaitable
 */
function fetch($request, array $options = [])
{
    $request = is_string($request) ? createRequest($request, 'GET') : (is_array($request) ? array_map(function($req) {
        return is_string($req) ? createRequest($req) : $req;
    }, $request): $request);
    return is_array($request) ? join(...array_map(function ($req) use ($options) {
        return request($req, $options);
    }, $request)) : async(request($request, $options));
}
