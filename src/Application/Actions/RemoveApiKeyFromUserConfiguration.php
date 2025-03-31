<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use YSOCode\Commit\Application\Commands\Interfaces\RemoveApiKeyInterface;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Foundation\Support\UserConfiguration;

readonly class RemoveApiKeyFromUserConfiguration implements RemoveApiKeyInterface
{
    public function __construct(private UserConfiguration $userConfiguration) {}

    public function execute(AiProvider $provider): true|Error
    {
        $apiKeyIsRemoved = $this->userConfiguration->setValue("ai_providers.{$provider->value}.api_key", null);
        if ($apiKeyIsRemoved instanceof Error) {
            return $apiKeyIsRemoved;
        }

        return true;
    }
}
