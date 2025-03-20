<?php

declare(strict_types=1);

namespace Console\Commands;

use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Feature\Console\Commands\Traits\WithConfigurationToolsTrait;
use Tests\Feature\Console\Commands\Traits\WithSymfonyConsoleApplicationTrait;
use YSOCode\Commit\Application\Console\Commands\InitializeConfiguration;

class InitializeConfigurationTest extends TestCase
{
    use WithConfigurationToolsTrait, WithSymfonyConsoleApplicationTrait;

    protected function setUp(): void
    {
        $this->setUpUserConfiguration();
        $this->setUpSymfonyConsoleApplication();

        $this->app->add(new InitializeConfiguration($this->userConfiguration));
    }

    /**
     * @throws Exception
     */
    private function removeConfigurationDir(): void
    {
        deleteDirectory($this->userConfiguration->getUserConfigurationDirPath());
    }

    private function createConfigurationFile(): void
    {
        $configDir = $this->userConfiguration->getUserConfigurationDirPath();

        if (! is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }

        touch($this->userConfiguration->getUserConfigurationFilePath());
    }

    /**
     * @throws Exception
     */
    public function test_it_should_create_configuration_file_when_not_exists(): void
    {
        $this->removeConfigurationDir();

        $tester = new CommandTester($this->app->find('init'));
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Success: Configuration initialized!', $tester->getDisplay());
    }

    public function test_it_should_create_configuration_file_when_exists_with_force_option(): void
    {
        $this->createConfigurationFile();

        $tester = new CommandTester($this->app->find('init'));
        $tester->execute(['--force' => true]);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Success: Configuration initialized!', $tester->getDisplay());
    }

    public function test_it_should_not_create_configuration_file_when_exists_without_force_option(): void
    {
        $this->createConfigurationFile();

        $tester = new CommandTester($this->app->find('init'));
        $tester->execute([]);

        $this->assertStringContainsString('Error: User configuration file already exists.', $tester->getDisplay());
    }
}
