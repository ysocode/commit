<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use Exception;
use PHPUnit\Framework\MockObject\Exception as PHPUnitMockObjectException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Feature\Console\Commands\Traits\WithConfigurationToolsTrait;
use Tests\Feature\Console\Commands\Traits\WithSymfonyConsoleApplicationTrait;
use YSOCode\Commit\Application\Actions\CheckAiProviderIsEnabledInUserConfiguration;
use YSOCode\Commit\Application\Actions\CheckLanguageIsEnabledInUserConfiguration;
use YSOCode\Commit\Application\Actions\GetDefaultAiProviderFromUserConfiguration;
use YSOCode\Commit\Application\Actions\GetDefaultLanguageFromUserConfiguration;
use YSOCode\Commit\Application\Commands\GenerateConventionalCommitMessage;
use YSOCode\Commit\Application\Commands\Interfaces\CommitStagedChangesInterface;
use YSOCode\Commit\Application\Commands\Interfaces\FetchStagedChangesInterface;
use YSOCode\Commit\Application\Commands\Interfaces\GenerateCommitMessageInterface;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Enums\Language;
use YSOCode\Commit\Domain\Types\Error;

class GenerateConventionalCommitMessageTest extends TestCase
{
    use WithConfigurationToolsTrait, WithSymfonyConsoleApplicationTrait;

    private MockObject $mockFetchStagedChanges;

    private MockObject $mockGenerateCommitMessage;

    private MockObject $mockCommitStagedChanges;

    private string $diff = <<<'DIFF'
        index 6711592..6108c39 100644
        --- a/Example.php
        +++ b/Example.php
        @@ -2,9 +2,9 @@
        
         class Example
         {
        -    public function helloWorld(): string
        +    public function helloYsoCode(): string
             {
        -        return 'Hello world';
        +        return 'Hello YSO Code';
             }
         }
        DIFF;

    private string $expectedCommitMessage = 'feat: rename hello world function to hello YSO Code';

    private AiProvider $aiProvider = AiProvider::SOURCEGRAPH;

    private Language $language = Language::EN_US;

    /**
     * @throws PHPUnitMockObjectException
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::setUpUserConfiguration();

        self::createUserConfigurationFile();

        $this->setUpSymfonyConsoleApplication();

        self::$userConfiguration->setValue('default_ai_provider', $this->aiProvider->value);
        self::$userConfiguration->setValue('default_lang', $this->language->value);

        $this->mockFetchStagedChanges = $this->createMock(FetchStagedChangesInterface::class);

        $this->mockGenerateCommitMessage = $this->createMock(GenerateCommitMessageInterface::class);

        $this->mockCommitStagedChanges = $this->createMock(CommitStagedChangesInterface::class);

        $checkAiProviderIsEnabled = new CheckAiProviderIsEnabledInUserConfiguration(self::$userConfiguration);
        $checkLanguageIsEnabled = new CheckLanguageIsEnabledInUserConfiguration(self::$userConfiguration);

        $this->app->add(
            new GenerateConventionalCommitMessage(
                $checkAiProviderIsEnabled,
                $checkLanguageIsEnabled,
                new GetDefaultAiProviderFromUserConfiguration(
                    self::$userConfiguration,
                    $checkAiProviderIsEnabled
                ),
                new GetDefaultLanguageFromUserConfiguration(
                    self::$userConfiguration,
                    $checkLanguageIsEnabled
                ),
                $this->mockFetchStagedChanges,
                $this->mockGenerateCommitMessage,
                $this->mockCommitStagedChanges
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

    private function configureMockGenerateCommitMessage(
        InvocationOrder $subscribeExpectation,
        InvocationOrder $createExpectation
    ): void {
        $this->mockGenerateCommitMessage
            ->expects($subscribeExpectation)
            ->method('subscribe')
            ->with($this->isCallable());

        $this->mockGenerateCommitMessage
            ->expects($createExpectation)
            ->method('execute')
            ->willReturn(<<<COMMIT_MESSAGE
            ```
            $this->expectedCommitMessage
            ```
            COMMIT_MESSAGE);
    }

    /**
     * @throws Exception
     */
    private function getDefaultAiProvider(): AiProvider
    {
        $defaultAiProvider = self::$userConfiguration->getValue('default_ai_provider');
        if ($defaultAiProvider instanceof Error) {
            throw new Exception((string) $defaultAiProvider);
        }

        if (! is_string($defaultAiProvider)) {
            throw new Exception('Default AI provider should be a string.');
        }

        $defaultAiProviderAsEnum = AiProvider::parse($defaultAiProvider);
        if ($defaultAiProviderAsEnum instanceof Error) {
            throw new Exception((string) $defaultAiProviderAsEnum);
        }

        return $defaultAiProviderAsEnum;
    }

    /**
     * @throws Exception
     */
    private function getDefaultLanguage(): Language
    {
        $defaultLanguage = self::$userConfiguration->getValue('default_lang');
        if ($defaultLanguage instanceof Error) {
            throw new Exception((string) $defaultLanguage);
        }

        if (! is_string($defaultLanguage)) {
            throw new Exception('Default language should be a string.');
        }

        $defaultLanguageAsEnum = Language::parse($defaultLanguage);
        if ($defaultLanguageAsEnum instanceof Error) {
            throw new Exception((string) $defaultLanguageAsEnum);
        }

        return $defaultLanguageAsEnum;
    }

    public function test_it_should_fetch_staged_changes(): void
    {
        $this->mockFetchStagedChanges
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->diff);

        $this->configureMockGenerateCommitMessage(
            $this->once(),
            $this->once()
        );

        $this->mockCommitStagedChanges
            ->expects($this->never())
            ->method('execute')
            ->with(
                $this->equalTo($this->expectedCommitMessage)
            );

        $tester = new CommandTester($this->app->find('generate'));
        $tester->setInputs(['n']);
        $tester->execute([]);

        $output = $tester->getDisplay();

        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString($this->expectedCommitMessage, $output);
        $this->assertStringContainsString('Success: No commit made.', $output);
    }

    public function test_it_should_display_error_when_no_staged_changes_found(): void
    {
        $this->mockFetchStagedChanges
            ->expects($this->once())
            ->method('execute')
            ->willReturn(
                Error::parse('No staged changes found.')
            );

        $this->configureMockGenerateCommitMessage(
            $this->never(),
            $this->never()
        );

        $this->mockCommitStagedChanges
            ->expects($this->never())
            ->method('execute');

        $tester = new CommandTester($this->app->find('generate'));
        $tester->execute([]);

        $output = $tester->getDisplay();

        $this->assertEquals(
            1,
            $tester->getStatusCode()
        );

        $this->assertStringContainsString('No staged changes found.', $output);
    }

    public function test_it_should_use_provided_diff_argument_instead_of_fetching_staged_changes(): void
    {
        $this->mockFetchStagedChanges
            ->expects($this->never())
            ->method('execute');

        $this->configureMockGenerateCommitMessage(
            $this->once(),
            $this->once()
        );

        $this->mockCommitStagedChanges
            ->expects($this->never())
            ->method('execute');

        $tester = new CommandTester($this->app->find('generate'));
        $tester->setInputs(['n']);
        $tester->execute([
            'diff' => $this->diff,
        ]);

        $output = $tester->getDisplay();

        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString($this->expectedCommitMessage, $output);
        $this->assertStringContainsString('Success: No commit made.', $output);
    }

    /**
     * @throws Exception
     */
    public function test_it_should_use_provided_ai_provider_option_instead_of_default(): void
    {
        $this->mockFetchStagedChanges
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->diff);

        $this->configureMockGenerateCommitMessage(
            $this->once(),
            $this->once()
        );

        $this->mockCommitStagedChanges
            ->expects($this->never())
            ->method('execute');

        $aiProvider = AiProvider::SOURCEGRAPH;

        $defaultLanguage = self::$userConfiguration->getValue('default_lang');
        if ($defaultLanguage instanceof Error) {
            throw new Exception((string) $defaultLanguage);
        }

        if (! is_string($defaultLanguage)) {
            throw new Exception('Default language should be a string.');
        }

        $language = Language::parse($defaultLanguage);
        if ($language instanceof Error) {
            throw new Exception((string) $language);
        }

        $tester = new CommandTester($this->app->find('generate'));
        $tester->setInputs(['n']);
        $tester->execute([
            '--provider' => $aiProvider->value,
        ]);

        $output = $tester->getDisplay();

        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString("Below is the generated commit message [AI: {$aiProvider->getFormattedValue()} | Lang: {$language->getFormattedValue()}]:", $output);
        $this->assertStringContainsString($this->expectedCommitMessage, $output);
        $this->assertStringContainsString('Success: No commit made.', $output);
    }

    /**
     * @throws Exception
     */
    public function test_it_should_display_error_when_ai_provider_is_not_enabled(): void
    {
        $this->mockFetchStagedChanges
            ->expects($this->never())
            ->method('execute');

        $this->configureMockGenerateCommitMessage(
            $this->never(),
            $this->never()
        );

        $this->mockCommitStagedChanges
            ->expects($this->never())
            ->method('execute');

        $defaultAiProvider = $this->getDefaultAiProvider();

        self::$userConfiguration->setValue("ai_providers.{$defaultAiProvider->value}.enabled", false);

        $tester = new CommandTester($this->app->find('generate'));
        $tester->execute([]);

        $output = $tester->getDisplay();

        $this->assertEquals(
            1,
            $tester->getStatusCode()
        );

        $this->assertStringContainsString(
            sprintf(
                'Error: The "%s" AI provider is not enabled.',
                $defaultAiProvider->getFormattedValue()
            ),
            $output
        );
    }

    /**
     * @throws Exception
     */
    public function test_it_should_display_error_when_ai_provider_is_not_enabled_using_provider_option(): void
    {
        $this->mockFetchStagedChanges
            ->expects($this->never())
            ->method('execute');

        $this->configureMockGenerateCommitMessage(
            $this->never(),
            $this->never()
        );

        $this->mockCommitStagedChanges
            ->expects($this->never())
            ->method('execute');

        $aiProvider = AiProvider::SOURCEGRAPH;

        self::$userConfiguration->setValue("ai_providers.{$aiProvider->value}.enabled", false);

        $tester = new CommandTester($this->app->find('generate'));
        $tester->execute([
            '--provider' => $aiProvider->value,
        ]);

        $output = $tester->getDisplay();

        $this->assertEquals(
            1,
            $tester->getStatusCode()
        );

        $this->assertStringContainsString(
            sprintf(
                'Error: The "%s" AI provider is not enabled.',
                $aiProvider->getFormattedValue()
            ),
            $output
        );
    }

    /**
     * @throws Exception
     */
    public function test_it_should_use_provided_lang_option_instead_of_default(): void
    {
        $this->mockFetchStagedChanges
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->diff);

        $this->configureMockGenerateCommitMessage(
            $this->once(),
            $this->once()
        );

        $this->mockCommitStagedChanges
            ->expects($this->never())
            ->method('execute');

        $defaultAiProvider = self::$userConfiguration->getValue('default_ai_provider');
        if ($defaultAiProvider instanceof Error) {
            throw new Exception((string) $defaultAiProvider);
        }

        if (! is_string($defaultAiProvider)) {
            throw new Exception('Default AI provider should be a string.');
        }

        $aiProvider = AiProvider::parse($defaultAiProvider);
        if ($aiProvider instanceof Error) {
            throw new Exception((string) $aiProvider);
        }

        $language = Language::PT_BR;

        $tester = new CommandTester($this->app->find('generate'));
        $tester->setInputs(['n']);
        $tester->execute([
            '--lang' => $language->value,
        ]);

        $output = $tester->getDisplay();

        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString("Below is the generated commit message [AI: {$aiProvider->getFormattedValue()} | Lang: {$language->getFormattedValue()}]:", $output);
        $this->assertStringContainsString($this->expectedCommitMessage, $output);
        $this->assertStringContainsString('Success: No commit made.', $output);
    }

    /**
     * @throws Exception
     */
    public function test_it_should_display_error_when_language_is_not_enabled(): void
    {
        $this->mockFetchStagedChanges
            ->expects($this->never())
            ->method('execute');

        $this->configureMockGenerateCommitMessage(
            $this->never(),
            $this->never()
        );

        $this->mockCommitStagedChanges
            ->expects($this->never())
            ->method('execute');

        $defaultLanguage = $this->getDefaultLanguage();

        self::$userConfiguration->setValue("languages.{$defaultLanguage->value}.enabled", false);

        $tester = new CommandTester($this->app->find('generate'));
        $tester->execute([]);

        $output = $tester->getDisplay();

        $this->assertEquals(
            1,
            $tester->getStatusCode()
        );

        $this->assertStringContainsString(
            sprintf(
                'Error: The "%s" language is not enabled.',
                $defaultLanguage->getFormattedValue()
            ),
            $output
        );
    }

    public function test_it_should_display_error_when_language_is_not_enabled_using_lang_option(): void
    {
        $this->mockFetchStagedChanges
            ->expects($this->never())
            ->method('execute');

        $this->configureMockGenerateCommitMessage(
            $this->never(),
            $this->never()
        );

        $this->mockCommitStagedChanges
            ->expects($this->never())
            ->method('execute');

        $language = Language::EN_US;

        self::$userConfiguration->setValue("languages.{$language->value}.enabled", false);

        $tester = new CommandTester($this->app->find('generate'));
        $tester->execute([
            '--lang' => $language->value,
        ]);

        $output = $tester->getDisplay();

        $this->assertEquals(
            1,
            $tester->getStatusCode()
        );

        $this->assertStringContainsString(
            sprintf(
                'Error: The "%s" language is not enabled.',
                $language->getFormattedValue()
            ),
            $output
        );
    }
}
