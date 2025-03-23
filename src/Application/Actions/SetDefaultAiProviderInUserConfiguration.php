<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use YSOCode\Commit\Application\Console\Commands\Interfaces\SetDefaultAiProviderInterface;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Foundation\Support\UserConfiguration;

readonly class SetDefaultAiProviderInUserConfiguration implements SetDefaultAiProviderInterface
{
    public function __construct(private UserConfiguration $userConfiguration) {}

    public function execute(AiProvider $aiProvider): true|Error
    {
        $setValueReturn = $this->userConfiguration->setValue('default_ai_provider', $aiProvider->value);
        if ($setValueReturn instanceof Error) {
            return Error::parse('Unable to set default AI provider.');
        }

        return true;
    }
}
