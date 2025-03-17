<?php

declare(strict_types=1);

namespace Tests\Feature;

use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use YSOCode\Commit\Application\Console\InitializeConfiguration\InitializeConfiguration;

class InitializeConfigurationTest extends TestCase
{
    use WithConsoleConfigurationTrait;

    protected function setUp(): void
    {
        $this->setUpConsoleConfiguration();
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
