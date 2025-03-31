<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use YSOCode\Commit\Application\Commands\Interfaces\GetDefaultModelInterface;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Foundation\Support\UserConfiguration;

readonly class GetDefaultModelFromUserConfiguration implements GetDefaultModelInterface
{
    public function __construct(private UserConfiguration $userConfiguration) {}

    public function execute(AiProvider $aiProvider): string|Error
    {
        $defaultModel = $this->userConfiguration->getValue("ai_providers.{$aiProvider->value}.default_model");
        if (! $defaultModel || ! is_string($defaultModel)) {
            return Error::parse(
                sprintf('Invalid default model for "%s" AI provider.', $aiProvider->value),
            );
        }

        return $defaultModel;
    }
}
