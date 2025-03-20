<?php

declare(strict_types=1);

namespace Tests\Feature;

use Symfony\Component\Console\Application;

trait WithSymfonyConsoleApplicationTrait
{
    private Application $app;

    private function setUpSymfonyConsoleApplication(): void
    {
        $this->app = new Application;
    }
}
