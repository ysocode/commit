<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Feature\Console\Commands\Traits\WithConfigurationToolsTrait;
use Tests\Feature\Console\Commands\Traits\WithSymfonyConsoleApplicationTrait;
use YSOCode\Commit\Application\Actions\CheckAiProviderIsEnabledInUserConfiguration;
use YSOCode\Commit\Application\Actions\FetchEnabledAiProvidersFromUserConfiguration;
use YSOCode\Commit\Application\Actions\GetDefaultAiProviderFromUserConfiguration;
use YSOCode\Commit\Application\Actions\SetDefaultAiProviderInUserConfiguration;
use YSOCode\Commit\Application\Commands\ManageDefaultAiProvider;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;

class ManageDefaultAiProviderTest extends TestCase
{
    use WithConfigurationToolsTrait, WithSymfonyConsoleApplicationTrait;

    private AiProvider $aiProvider = AiProvider::SOURCEGRAPH;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::setUpUserConfiguration();

        self::createUserConfigurationFile();

        $this->setUpSymfonyConsoleApplication();

        self::$userConfiguration->setValue('default_ai_provider', $this->aiProvider->value);

        $this->app->add(
            new ManageDefaultAiProvider(
                new GetDefaultAiProviderFromUserConfiguration(
                    self::$userConfiguration,
                    new CheckAiProviderIsEnabledInUserConfiguration(self::$userConfiguration)
                ),
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

    /**
     * @return array<AiProvider>
     *
     * @throws Exception
     */
    private function getEnabledAiProviders(): array
    {
        $aiProviders = self::$userConfiguration->getValue('ai_providers');
        if ($aiProviders instanceof Error) {
            throw new Exception((string) $aiProviders);
        }

        if (! $aiProviders || ! is_array($aiProviders)) {
            throw new Exception('Unable to get AI providers.');
        }
        $convertedAiProviders = [];

        foreach ($aiProviders as $aiProvider => $aiProviderConfigurations) {
            $convertedAiProvider = AiProvider::parse($aiProvider);
            if ($convertedAiProvider instanceof Error) {
                throw new Exception((string) $convertedAiProvider);
            }

            if (! is_array($aiProviderConfigurations)) {
                throw new Exception("{$convertedAiProvider->getFormattedValue()} AI provider configurations should be an array.");
            }

            if (! $aiProviderConfigurations['enabled']) {
                continue;
            }

            $convertedAiProviders[] = $convertedAiProvider;

        }

        if ($convertedAiProviders === []) {
            throw new Exception('No enabled AI providers found.');
        }

        return $convertedAiProviders;
    }

    /**
     * @throws Exception
     */
    public function test_it_should_list_all_enabled_ai_providers(): void
    {
        $tester = new CommandTester($this->app->find('ai:provider'));
        $tester->execute([
            '--list' => true,
        ]);

        $output = $tester->getDisplay();

        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString(
            'Enabled AI Providers:',
            $output
        );

        $enabledAiProviders = $this->getEnabledAiProviders();
        foreach ($enabledAiProviders as $aiProvider) {
            $this->assertStringContainsString(
                $aiProvider->getFormattedValue(),
                $output
            );
        }
    }

    public function test_it_should_display_error_when_provider_argument_is_provided_with_list_option(): void
    {
        $tester = new CommandTester($this->app->find('ai:provider'));
        $tester->execute([
            '--list' => true,
            'provider' => $this->aiProvider->value,
        ]);

        $output = $tester->getDisplay();

        $this->assertEquals(
            1,
            $tester->getStatusCode()
        );

        $this->assertStringContainsString(
            'Error: The "--list" option cannot be used with the "provider" argument.',
            $output
        );
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
            $this->aiProvider->getFormattedValue(),
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
            'Success: Default AI provider set successfully!',
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
            'Success: Default AI provider set successfully!',
            $output
        );
    }

    /**
     * @throws Exception
     */
    public function test_it_display_error_when_ai_providers_are_not_enabled(): void
    {
        $aiProviders = self::$userConfiguration->getValue('ai_providers');
        if ($aiProviders instanceof Error) {
            throw new Exception((string) $aiProviders);
        }

        if (! is_array($aiProviders)) {
            throw new Exception('AI providers should be an array.');
        }

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
}
