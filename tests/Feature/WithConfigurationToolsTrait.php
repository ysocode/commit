<?php

declare(strict_types=1);

namespace Tests\Feature;

use YSOCode\Commit\Foundation\Support\Configuration;
use YSOCode\Commit\Foundation\Support\UserConfiguration;

trait WithConfigurationToolsTrait
{
    private UserConfiguration $userConfiguration;

    private function setUpUserConfiguration(): void
    {
        $this->userConfiguration = new UserConfiguration(
            new Configuration([
                'app' => [
                    'home_directory' => __DIR__.'/../../storage/tmp',
                    'main_directory' => '.ysocode',
                    'package_directory' => 'commit',
                ],
            ])
        );
    }
}
