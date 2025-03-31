<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Services;

use Closure;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use YSOCode\Commit\Application\Services\Interfaces\AiProviderServiceInterface;
use YSOCode\Commit\Domain\Types\CohereApiKey;
use YSOCode\Commit\Domain\Types\Error;

readonly class Cohere implements AiProviderServiceInterface
{
    private const string API_ENDPOINT = 'https://api.cohere.com/v2/chat';

    /** @var array<string> */
    private const array ERROR_MESSAGES = [
        400 => 'V2chat Request Bad Request Error',
        401 => 'V2chat Request Unauthorized Error',
        403 => 'V2chat Request Forbidden Error',
        404 => 'V2chat Request Not Found Error',
        422 => 'V2chat Request Unprocessable Entity Error',
        429 => 'V2chat Request Too Many Requests Error',
        498 => 'V2chat Request Invalid Token Error',
        499 => 'V2chat Request Client Closed Request Error',
        500 => 'V2chat Request Internal Server Error',
        501 => 'V2chat Request Not Implemented Error',
        503 => 'V2chat Request Service Unavailable Error',
        504 => 'V2chat Request Gateway Timeout Error',
    ];

    public function __construct(
        private CohereApiKey $apiKey,
        private string $model,
        private float $temperature
    ) {}

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function generateCommitMessage(string $prompt, string $diff, Closure $onProgress): string|Error
    {
        $httpClient = HttpClient::create()
            ->withOptions([
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer {$this->apiKey}",
                ],
                'on_progress' => $onProgress,
            ]);

        $response = $httpClient->request('POST', self::API_ENDPOINT, [
            'body' => json_encode([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $prompt,
                    ],
                    [
                        'role' => 'user',
                        'content' => $diff,
                    ],
                ],
                'temperature' => $this->temperature,
            ]),
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            $errorMessage = self::ERROR_MESSAGES[$statusCode] ?? 'Unknown error';

            return Error::parse(sprintf('Cohere API error "%s".', $errorMessage));
        }

        $body = json_decode($response->getContent(), true);
        if (! $body || ! is_array($body)) {
            return Error::parse('The response body is missing or malformed.');
        }

        $message = $body['message'] ?? null;
        if (! $message || ! is_array($message)) {
            return Error::parse('The message structure does not match the expected format.');
        }

        $content = $message['content'] ?? null;
        if (! $content || ! is_array($content)) {
            return Error::parse('The content structure does not match the expected format.');
        }

        [$firstContent] = $content;
        if (! $firstContent || ! is_array($firstContent)) {
            return Error::parse('The first content has an unexpected format.');
        }

        ['type' => $type, 'text' => $text] = $firstContent;

        if ($type !== 'text') {
            return Error::parse('The first content type is not supported.');
        }

        if (! $text || ! is_string($text)) {
            return Error::parse('The generated text for the first content is missing or invalid.');
        }

        return $text;
    }
}
