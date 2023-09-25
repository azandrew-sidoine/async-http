<?php

namespace Drewlabs\Async\Http\Traits;

trait Message
{

    /**
     * @var string
     */
    private $body;

    /**
     * @var array<string,mixed>
     */
    private $headers;

    public function getHeaders()
    {
        return $this->headers ?? [];
    }

    public function getHeader(string $name)
    {
        if (empty($headers = $this->getHeaders())) {
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


    public function hasHeader($name)
    {
        return null !== $this->getHeader($name);
    }

    public function withHeader($name, $value)
    {
        $self  = clone $this;

        $self->headers[$name] = $value;

        return $self;
    }

    public function getBody()
    {
        return $this->body;
    }
}
