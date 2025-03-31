<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Feature\Console\Commands\Traits\WithConfigurationToolsTrait;
use Tests\Feature\Console\Commands\Traits\WithSymfonyConsoleApplicationTrait;
use YSOCode\Commit\Application\Actions\CheckAiProviderIsEnabledInUserConfiguration;
use YSOCode\Commit\Application\Actions\GetApiKeyFromUserConfiguration;
use YSOCode\Commit\Application\Actions\GetDefaultAiProviderFromUserConfiguration;
use YSOCode\Commit\Application\Actions\RemoveApiKeyFromUserConfiguration;
use YSOCode\Commit\Application\Actions\SetApiKeyInUserConfiguration;
use YSOCode\Commit\Application\Commands\ManageAiProviderApiKey;
use YSOCode\Commit\Domain\Enums\AiProvider;

class ManageAiProviderApiKeyTest extends TestCase
{
    use WithConfigurationToolsTrait, WithSymfonyConsoleApplicationTrait;

    private AiProvider $aiProvider = AiProvider::SOURCEGRAPH;

    private string $fakeApiKey = 'sgp_deadbeefcafebabe_ffffffffffffffffffffffffffffffffffffffff';

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::setUpUserConfiguration();

        self::removeUserConfigurationDir();
        self::createUserConfigurationFile();

        $this->setUpSymfonyConsoleApplication();

        self::$userConfiguration->setValue('default_ai_provider', $this->aiProvider->value);

        $checkAiProviderIsEnabled = new CheckAiProviderIsEnabledInUserConfiguration(self::$userConfiguration);

        $this->app->add(
            new ManageAiProviderApiKey(
                $checkAiProviderIsEnabled,
                new GetDefaultAiProviderFromUserConfiguration(self::$userConfiguration, $checkAiProviderIsEnabled),
                new GetApiKeyFromUserConfiguration(self::$userConfiguration),
                new RemoveApiKeyFromUserConfiguration(self::$userConfiguration),
                new SetApiKeyInUserConfiguration(self::$userConfiguration)
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
     * @throws Exception
     */
    public function test_it_should_get_the_api_key(): void
    {
        self::$userConfiguration->setValue("ai_providers.{$this->aiProvider->value}.api_key", $this->fakeApiKey);

        $tester = new CommandTester($this->app->find('ai:api-key'));
        $tester->execute([
            '--get' => true,
        ]);

        $output = $tester->getDisplay();

        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString($this->fakeApiKey, $output);
    }

    public function test_it_should_display_error_when_no_api_key_found(): void
    {
        self::$userConfiguration->setValue("ai_providers.{$this->aiProvider->value}.api_key", '');

        $tester = new CommandTester($this->app->find('ai:api-key'));
        $tester->execute([
            '--get' => true,
        ]);

        $output = $tester->getDisplay();

        $this->assertEquals(
            1,
            $tester->getStatusCode(),
        );

        $this->assertStringContainsString(
            sprintf('Error: Invalid API key for "%s" AI provider.', $this->aiProvider->getFormattedValue()),
            $output
        );
    }

    public function test_it_should_use_provided_ai_provider_option_instead_of_default(): void
    {
        self::$userConfiguration->setValue("ai_providers.{$this->aiProvider->value}.api_key", $this->fakeApiKey);

        $tester = new CommandTester($this->app->find('ai:api-key'));
        $tester->execute([
            '--get' => true,
            '--provider' => $this->aiProvider->value,
        ]);

        $output = $tester->getDisplay();

        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString($this->fakeApiKey, $output);
    }

    public function test_it_should_remove_the_api_key(): void
    {
        self::$userConfiguration->setValue("ai_providers.{$this->aiProvider->value}.api_key", $this->fakeApiKey);

        $tester = new CommandTester($this->app->find('ai:api-key'));
        $tester->execute([
            '--remove' => true,
        ]);

        $output = $tester->getDisplay();

        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('Success: API key removed successfully!', $output);

        $apiKey = self::$userConfiguration->getValue("ai_providers.{$this->aiProvider->value}.api_key");
        $this->assertNull($apiKey);
    }
}
