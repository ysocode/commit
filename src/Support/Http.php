<?php

namespace YSOCode\Commit\Support;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use YSOCode\Commit\Domain\Types\Error;

readonly class Http
{
    private HttpClientInterface $httpClient;

    public function __construct(?string $apiKey = null)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($apiKey) {
            $headers['Authorization'] = "Bearer {$apiKey}";
        }

        $httpClient = HttpClient::create();
        $this->httpClient = $httpClient->withOptions([
            'headers' => $headers,
        ]);
    }

    public static function create(?string $apiKey = null): self
    {
        return new self($apiKey);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>|Error
     */
    public function post(string $url, array $body, callable $onProgress): array|Error
    {
        $response = $this->httpClient->request('POST', $url, [
            'body' => $this->parseBody($body),
            'on_progress' => $onProgress,
        ]);

        if ($response->getStatusCode() !== 200) {
            return Error::parse(
                "Request to {$url} failed with status code {$response->getStatusCode()}"
            );
        }

        $responseBody = $response->toArray(false);
        if (! $responseBody || ! hasOnlyStringKeys($responseBody)) {
            return Error::parse('Invalid JSON response from API or unexpected response format');
        }

        /** @var array<string, mixed> $responseBody */
        return $responseBody;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function parseBody(array $body): string
    {
        return json_encode($body, JSON_THROW_ON_ERROR);
    }
}
