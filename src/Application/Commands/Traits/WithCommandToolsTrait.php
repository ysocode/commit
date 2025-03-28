<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Commands\Traits;

use Symfony\Component\Console\Input\InputInterface;
use YSOCode\Commit\Domain\Types\Error;

trait WithCommandToolsTrait
{
    private function checkArgumentIsProvided(InputInterface $input, string $argument): bool|Error
    {
        if (! $input->hasArgument($argument)) {
            return Error::parse(sprintf('Argument "%s" is not defined in the command.', $argument));
        }

        return ! is_null($input->getArgument($argument));
    }

    private function checkOptionIsProvided(InputInterface $input, string $option): bool|Error
    {
        if (! $input->hasOption($option)) {
            return Error::parse(sprintf('Option "%s" is not defined in the command.', $option));
        }

        return ! is_null($input->getOption($option));
    }

    private function getBooleanOption(InputInterface $input, string $option): bool|Error
    {
        if (! $input->hasOption($option)) {
            return Error::parse(sprintf('Option "%s" is not defined in the command.', $option));
        }

        $optionValue = $input->getOption($option);
        if (! is_bool($optionValue)) {
            return Error::parse(sprintf('Invalid "%s" option provided.', $option));
        }

        return $optionValue;
    }
}
