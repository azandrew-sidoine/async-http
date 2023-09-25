<?php

namespace Drewlabs\Async\Http\Contracts;

interface RequestException
{
    /**
     * Returns true if exception instance has a response object
     * 
     * @return bool 
     */
    public function hasResponse();

    /**
     * Returns the actual response object
     * 
     * @return ResponseInterface 
     */
    public function getResponse();

}