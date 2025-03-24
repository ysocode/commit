<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Feature\Console\Commands\Traits\WithConfigurationToolsTrait;
use Tests\Feature\Console\Commands\Traits\WithSymfonyConsoleApplicationTrait;
use YSOCode\Commit\Application\Actions\FetchEnabledAiProvidersFromUserConfiguration;
use YSOCode\Commit\Application\Actions\GetDefaultAiProviderFromUserConfiguration;
use YSOCode\Commit\Application\Actions\SetDefaultAiProviderInUserConfiguration;
use YSOCode\Commit\Application\Console\Commands\ManageDefaultAiProvider;
use YSOCode\Commit\Domain\Enums\AiProvider;

class ManageDefaultAiProviderTest extends TestCase
{
    use WithConfigurationToolsTrait, WithSymfonyConsoleApplicationTrait;

    private readonly AiProvider $aiProvider;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::setUpUserConfiguration();

        self::createUserConfigurationFile();

        $this->setUpSymfonyConsoleApplication();

        $this->aiProvider = AiProvider::SOURCEGRAPH;
        self::$userConfiguration->setValue('default_ai_provider', $this->aiProvider->value);

        $this->app->add(
            new ManageDefaultAiProvider(
                new GetDefaultAiProviderFromUserConfiguration(self::$userConfiguration),
                new SetDefaultAiProviderInUserConfiguration(self::$userConfiguration),
                new FetchEnabledAiProvidersFromUserConfiguration(self::$userConfiguration)
            )
        );
    }

    /**
     * @throws Exception
     */
    public static function tearDownAfterClass(): void
    {
        self::removeUserConfigurationDir();
    }

    public function test_it_should_get_the_current_default_ai_provider(): void
    {
        $tester = new CommandTester($this->app->find('ai:provider'));
        $tester->execute([
            '--get' => true,
        ]);

        $output = $tester->getDisplay();

        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString(
            "The current default AI provider is: {$this->aiProvider->formattedValue()}",
            $output
        );
    }

    public function test_it_should_display_error_when_ai_provier_is_not_set(): void
    {
        self::$userConfiguration->setValue('default_ai_provider', '');

        $tester = new CommandTester($this->app->find('ai:provider'));
        $tester->execute([
            '--get' => true,
        ]);

        $output = $tester->getDisplay();

        $this->assertEquals(
            1,
            $tester->getStatusCode()
        );

        $this->assertStringContainsString('Unable to get default AI provider.', $output);
    }

    public function test_it_should_display_error_when_provider_argument_is_provided_with_get_option(): void
    {
        $tester = new CommandTester($this->app->find('ai:provider'));
        $tester->execute([
            '--get' => true,
            'provider' => $this->aiProvider->value,
        ]);

        $output = $tester->getDisplay();

        $this->assertEquals(
            1,
            $tester->getStatusCode()
        );

        $this->assertStringContainsString(
            'Error: The "--get" option cannot be used with the "provider" argument.',
            $output
        );
    }

    public function test_it_should_set_the_default_ai_provider(): void
    {
        $tester = new CommandTester($this->app->find('ai:provider'));
        $tester->execute([
            'provider' => $this->aiProvider->value,
        ]);

        $output = $tester->getDisplay();

        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString(
            "The default AI provider has been set to: {$this->aiProvider->formattedValue()}",
            $output
        );
    }

    public function test_it_display_ai_provider_selection_when_provier_argument_is_not_provided(): void
    {
        $tester = new CommandTester($this->app->find('ai:provider'));
        $tester->setInputs([$this->aiProvider->value]);
        $tester->execute([]);

        $output = $tester->getDisplay();

        $tester->assertCommandIsSuccessful();

        [$firstAiProviderValue] = AiProvider::values() !== [] ? AiProvider::values() : [null];

        $this->assertStringContainsString(
            "Choose the AI provider to set as default [auto: {$firstAiProviderValue}]",
            $output
        );
        $this->assertStringContainsString(
            "The default AI provider has been set to: {$this->aiProvider->formattedValue()}",
            $output
        );
    }

    public function test_it_should_display_error_when_ai_provier_is_not_enabled(): void
    {
        self::$userConfiguration->setValue("ai_providers.{$this->aiProvider->value}.enabled", false);

        $tester = new CommandTester($this->app->find('ai:provider'));
        $tester->execute([
            'provider' => $this->aiProvider->value,
        ]);

        $output = $tester->getDisplay();

        $this->assertEquals(
            1,
            $tester->getStatusCode()
        );

        $this->assertStringContainsString(
            sprintf('AI provider "%s" is not enabled.', $this->aiProvider->value),
            $output
        );
    }

    public function test_it_display_error_when_ai_providers_are_not_enabled(): void
    {
        $aiProviders = self::$userConfiguration->getValue('ai_providers');

        foreach (array_keys($aiProviders) as $aiProvider) {
            self::$userConfiguration->setValue("ai_providers.{$aiProvider}.enabled", false);
        }

        $tester = new CommandTester($this->app->find('ai:provider'));
        $tester->execute([]);

        $output = $tester->getDisplay();

        $this->assertEquals(
            1,
            $tester->getStatusCode()
        );

        $this->assertStringContainsString(
            'No enabled AI providers found.',
            $output
        );
    }
}
