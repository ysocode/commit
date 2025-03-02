<?php

namespace YSOCode\Commit\Actions;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use YSOCode\Commit\Domain\Enums\AI;
use YSOCode\Commit\Domain\Types\Error;

readonly class GetCommitFromGitDiff implements Action
{
    const array AI_API_URLS = [
        'cohere' => 'https://api.cohere.com/v2/chat',
        'openai' => 'https://api.openai.com/v1/chat/completions',
    ];

    public function __construct(
        private AI $ai,
        private string $gitDiff
    ) {}

    /**
     * @throws ConnectionException
     */
    public function execute(): string|Error
    {
        $apiUrl = self::AI_API_URLS[$this->ai->value];
        $envName = strtoupper($this->ai->value).'_KEY';

        $apiKey = getenv($envName);
        if (! $apiKey) {
            return Error::parse("API key for {$this->ai->formattedValue()} not found in the configuration file");
        }

        $systemPrompt = <<<'PROMPT'
        You are an AI-powered Git commit message generator. 
        Analyze the given Git diff and generate a meaningful commit message following the 
        Conventional Commits specification (e.g., 'feat: add authentication'). 
        Never include file names or directory names. Keep it concise.
        NEVER use scopes in commit messages.
        PROMPT;

        $http = new Factory;
        $response = $http->accept('application/json')
            ->withToken($apiKey)
            ->post($apiUrl, [
                'model' => 'command-r-plus-08-2024',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt,
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->gitDiff,
                    ],
                ],
                'temperature' => 0.7,
            ]);

        if ($response->getStatusCode() !== 200) {
            return Error::parse("API request failed for {$this->ai->formattedValue()}");
        }

        $responseDecoded = json_decode($response->getBody(), true);
        if (! $responseDecoded || ! is_array($responseDecoded)) {
            return Error::parse('Unable to decode the response from the AI model');
        }

        $message = $responseDecoded['message'] ?? null;
        if (! $message || ! is_array($message)) {
            return Error::parse('Unable to retrieve the message from the AI model');
        }

        $content = $message['content'] ?? null;
        if (! $content || ! is_array($content)) {
            return Error::parse('Unable to retrieve the commit message from the AI model');
        }

        $contentFirstItem = $content[0] ?? null;
        if (! $contentFirstItem || ! is_array($contentFirstItem)) {
            return Error::parse('Unable to retrieve the first item of the commit message from the AI model');
        }

        $commitMessage = $contentFirstItem['text'] ?? null;
        if (! $commitMessage || ! is_string($commitMessage)) {
            return Error::parse('Unable to retrieve the text of the commit message from the AI model');
        }

        return $commitMessage;
    }
}
