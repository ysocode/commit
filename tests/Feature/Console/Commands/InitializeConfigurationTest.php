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

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->setUpUserConfiguration();

        self::createUserConfigurationFile();

        $this->setUpSymfonyConsoleApplication();

        $this->app->add(new InitializeConfiguration(self::$userConfiguration));
    }

    /**
     * @throws Exception
     */
    public static function tearDownAfterClass(): void
    {
        self::removeUserConfigurationDir();
    }

    /**
     * @throws Exception
     */
    public function test_it_should_create_configuration_file_when_not_exists(): void
    {
        self::removeUserConfigurationDir();

        $tester = new CommandTester($this->app->find('init'));
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Success: Configuration initialized!', $tester->getDisplay());
    }

    /**
     * @throws Exception
     */
    public function test_it_should_create_configuration_file_when_exists_with_force_option(): void
    {
        $tester = new CommandTester($this->app->find('init'));
        $tester->execute(['--force' => true]);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Success: Configuration initialized!', $tester->getDisplay());
    }

    /**
     * @throws Exception
     */
    public function test_it_should_not_create_configuration_file_when_exists_without_force_option(): void
    {
        $tester = new CommandTester($this->app->find('init'));
        $tester->execute([]);

        $this->assertStringContainsString('Error: User configuration file already exists.', $tester->getDisplay());
    }
}
