<?php

namespace AsyncAws\Core\HttpClient;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class AwsHttpClientFactory
{
    private const DEFAULT_MAX_ATTEMPTS = 3;
    private const ENV_MAX_ATTEMPTS = 'AWS_MAX_ATTEMPTS';

    public static function createRetryableClient(?HttpClientInterface $httpClient = null, ?LoggerInterface $logger = null): HttpClientInterface
    {
        if (null === $httpClient) {
            $httpClient = HttpClient::create();
        }
        if (class_exists(RetryableHttpClient::class)) {
            $maxAttempts = getenv(self::ENV_MAX_ATTEMPTS) ?:self::DEFAULT_MAX_ATTEMPTS;
            /** @psalm-suppress MissingDependency */
            $httpClient = new RetryableHttpClient(
                $httpClient,
                new AwsRetryStrategy(),
                $maxAttempts,
                $logger
            );
        }

        return $httpClient;
    }
}
