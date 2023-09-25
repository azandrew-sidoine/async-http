<?php

namespace Drewlabs\Async\Http\Contracts;

interface RequestInterface extends MessageInterface
{

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod();

    /**
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @return string Returns a url string representing the URI of the request.
     */
    public function getUrl();
}
