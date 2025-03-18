<?php

declare(strict_types=1);

namespace Tests\Feature;

use Symfony\Component\Console\Application;
use YSOCode\Commit\Foundation\Support\Configuration;
use YSOCode\Commit\Foundation\Support\UserConfiguration;

trait WithConsoleConfigurationTrait
{
    protected UserConfiguration $userConfiguration;

    protected Application $app;

    protected function setUpConsoleConfiguration(): void
    {
        $this->userConfiguration = new UserConfiguration(new Configuration([
            'app' => [
                'home_directory' => __DIR__.'/../../storage/tmp',
                'main_directory' => '.ysocode',
                'package_directory' => 'commit',
            ],
        ]));

        $this->app = new Application;
    }
}
