<?php

namespace AsyncAws\Core\Sts\Input;

use AsyncAws\Core\Request;
use AsyncAws\Core\Stream\StreamFactory;

class GetCallerIdentityRequest
{
    public static function create($input): self
    {
        return $input instanceof self ? $input : new self();
    }

    /**
     * @internal
     */
    public function request(): Request
    {
        // Prepare headers
        $headers = ['content-type' => 'application/x-www-form-urlencoded'];

        // Prepare query
        $query = [];

        // Prepare URI
        $uriString = '/';

        // Prepare Body
        $body = http_build_query(['Action' => 'GetCallerIdentity', 'Version' => '2011-06-15'] + $this->requestBody(), '', '&', \PHP_QUERY_RFC1738);

        // Return the Request
        return new Request('POST', $uriString, $query, $headers, StreamFactory::create($body));
    }

    public function validate(): void
    {
        // There are no required properties
    }

    /**
     * @internal
     */
    private function requestBody(): array
    {
        $payload = [];

        return $payload;
    }
}