<?php

declare(strict_types=1);

namespace Tests\Feature;

use Symfony\Component\Console\Application;
use YSOCode\Commit\Foundation\Support\Configuration;
use YSOCode\Commit\Foundation\Support\LocalConfiguration;

trait WithConsoleConfigurationTrait
{
    protected LocalConfiguration $localConfiguration;

    protected Application $app;

    protected function setUpConsoleConfiguration(): void
    {
        $this->localConfiguration = new LocalConfiguration(new Configuration([
            'app' => [
                'home_directory' => __DIR__.'/../../storage/tmp',
                'main_directory' => '.ysocode',
                'config_directory' => 'commit',
            ],
        ]));

        $this->app = new Application;
    }
}
