<?php

declare(strict_types=1);

namespace Tests\Feature;

use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use YSOCode\Commit\Application\Console\InitializeConfiguration\InitializeConfiguration;
use YSOCode\Commit\Foundation\Support\Configuration;
use YSOCode\Commit\Foundation\Support\LocalConfiguration;

class InitializeConfigurationTest extends TestCase
{
    private LocalConfiguration $localConfiguration;

    private Application $app;

    protected function setUp(): void
    {
        $this->localConfiguration = new LocalConfiguration(new Configuration([
            'app' => [
                'home_directory' => __DIR__.'/../../storage/tmp',
                'main_directory' => '.ysocode',
                'config_directory' => 'commit',
            ],
        ]));

        $this->app = new Application;
        $this->app->add(new InitializeConfiguration($this->localConfiguration));
    }

    /**
     * @throws Exception
     */
    private function removeConfigurationDir(): void
    {
        deleteDirectory($this->localConfiguration->getConfigurationDirPath());
    }

    private function createConfigurationFile(): void
    {
        $configDir = $this->localConfiguration->getConfigurationDirPath();

        if (! is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }

        touch($this->localConfiguration->getConfigurationFilePath());
    }

    /**
     * @throws Exception
     */
    public function test_it_can_create_configuration_file_when_not_exist(): void
    {
        $this->removeConfigurationDir();

        $tester = new CommandTester($this->app->find('init'));
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Success: Configuration initialized!', $tester->getDisplay());
    }

    public function test_it_can_create_configuration_file_when_exist_with_force_option(): void
    {
        $this->createConfigurationFile();

        $tester = new CommandTester($this->app->find('init'));
        $tester->execute(['--force' => true]);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Success: Configuration initialized!', $tester->getDisplay());
    }

    public function test_it_cannot_create_configuration_file_when_exist_without_force_option(): void
    {
        $this->createConfigurationFile();

        $tester = new CommandTester($this->app->find('init'));
        $tester->execute([]);

        $this->assertStringContainsString('Error: Configuration file already exists.', $tester->getDisplay());
    }
}
