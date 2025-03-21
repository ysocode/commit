<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Feature\Console\Commands\Traits\WithConfigurationToolsTrait;
use Tests\Feature\Console\Commands\Traits\WithSymfonyConsoleApplicationTrait;
use YSOCode\Commit\Application\Actions\GetDefaultAiProviderFromUserConfiguration;
use YSOCode\Commit\Application\Actions\GetDefaultLanguageFromUserConfiguration;
use YSOCode\Commit\Application\Console\Commands\Factories\GenerateCommitMessageFactory;
use YSOCode\Commit\Application\Console\Commands\GenerateConventionalCommitMessage;
use YSOCode\Commit\Application\Console\Commands\Interfaces\CommitStagedChangesInterface;
use YSOCode\Commit\Application\Console\Commands\Interfaces\FetchStagedChangesInterface;
use YSOCode\Commit\Application\Console\Commands\Interfaces\GenerateCommitMessageInterface;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Enums\Language;
use YSOCode\Commit\Domain\Enums\Status;
use YSOCode\Commit\Domain\Types\Error;

class GenerateConventionalCommitMessageTest extends TestCase
{
    use WithConfigurationToolsTrait, WithSymfonyConsoleApplicationTrait;

    private readonly FetchStagedChangesInterface $mockFetchStagedChanges;

    private readonly GenerateCommitMessageInterface $mockGenerateCommitMessage;

    private readonly CommitStagedChangesInterface $mockCommitStagedChanges;

    private readonly string $diff;

    private readonly string $expectedCommitMessage;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->setUpUserConfiguration();
        $this->setUpSymfonyConsoleApplication();

        $this->diff = <<<'DIFF'
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

        $this->expectedCommitMessage = 'feat: rename hello world function to hello YSO Code';

        $this->userConfiguration->setValue('default_ai_provider', 'sourcegraph');
        $this->userConfiguration->setValue('default_lang', 'en_US');

        $this->mockFetchStagedChanges = $this->createMock(FetchStagedChangesInterface::class);
        $this->mockGenerateCommitMessage = $this->createMock(GenerateCommitMessageInterface::class);
        $mockGenerateCommitMessageFactory = $this->createMock(GenerateCommitMessageFactory::class);
        $this->mockCommitStagedChanges = $this->createMock(CommitStagedChangesInterface::class);

        $mockGenerateCommitMessageFactory
            ->method('create')
            ->willReturn($this->mockGenerateCommitMessage);

        $this->app->add(
            new GenerateConventionalCommitMessage(
                new GetDefaultAiProviderFromUserConfiguration($this->userConfiguration),
                new GetDefaultLanguageFromUserConfiguration($this->userConfiguration),
                $this->mockFetchStagedChanges,
                $mockGenerateCommitMessageFactory,
                $this->mockCommitStagedChanges
            )
        );
    }

    public function configureObserverTraitMethods(
        InvocationOrder $subscribeExpectation,
        InvocationOrder $notifyExpectation
    ): void {
        $this->mockGenerateCommitMessage
            ->expects($subscribeExpectation)
            ->method('subscribe')
            ->with($this->isCallable());

        $this->mockGenerateCommitMessage
            ->expects($notifyExpectation)
            ->method('notify')
            ->with($this->isInstanceOf(Status::class));
    }

    public function test_it_should_fetch_staged_changes_when_no_diff_option_provided(): void
    {
        $this->mockFetchStagedChanges
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->diff);

        $this->configureObserverTraitMethods(
            $this->once(),
            $this->never(),
        );

        $this->mockGenerateCommitMessage
            ->expects($this->once())
            ->method('execute')
            ->with(
                $this->isString(),
                $this->equalTo($this->diff)
            )
            ->willReturn(<<<COMMIT_MESSAGE
            ```
            $this->expectedCommitMessage
            ```
            COMMIT_MESSAGE);

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

    public function test_it_should_use_provided_diff_option_instead_of_fetching_staged_changes(): void
    {
        $this->mockFetchStagedChanges
            ->expects($this->never())
            ->method('execute');

        $this->configureObserverTraitMethods(
            $this->once(),
            $this->never(),
        );

        $this->mockGenerateCommitMessage
            ->expects($this->once())
            ->method('execute')
            ->with(
                $this->isString(),
                $this->equalTo($this->diff)
            )
            ->willReturn(<<<COMMIT_MESSAGE
            ```
            $this->expectedCommitMessage
            ```
            COMMIT_MESSAGE);

        $this->mockCommitStagedChanges
            ->expects($this->never())
            ->method('execute');

        $tester = new CommandTester($this->app->find('generate'));
        $tester->setInputs(['n']);
        $tester->execute([
            '--diff' => $this->diff,
        ]);

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

        $this->configureObserverTraitMethods(
            $this->never(),
            $this->never(),
        );

        $this->mockGenerateCommitMessage
            ->expects($this->never())
            ->method('execute');

        $this->mockCommitStagedChanges
            ->expects($this->never())
            ->method('execute');

        $tester = new CommandTester($this->app->find('generate'));
        $tester->execute([]);

        $output = $tester->getDisplay();

        $this->assertStringContainsString('No staged changes found.', $output);
    }

    public function test_it_should_use_provided_ai_provider_option_instead_of_default(): void
    {
        $this->mockFetchStagedChanges
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->diff);

        $this->configureObserverTraitMethods(
            $this->once(),
            $this->never(),
        );

        $this->mockGenerateCommitMessage
            ->expects($this->once())
            ->method('execute')
            ->with(
                $this->isString(),
                $this->equalTo($this->diff)
            )
            ->willReturn(<<<COMMIT_MESSAGE
            ```
            $this->expectedCommitMessage
            ```
            COMMIT_MESSAGE);

        $this->mockCommitStagedChanges
            ->expects($this->never())
            ->method('execute');

        $aiProvider = AiProvider::SOURCEGRAPH;
        $language = Language::parse($this->userConfiguration->getValue('default_lang'));

        $tester = new CommandTester($this->app->find('generate'));
        $tester->setInputs(['n']);
        $tester->execute([
            '--provider' => $aiProvider->value,
        ]);

        $output = $tester->getDisplay();

        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString("Below is the generated commit message [AI: {$aiProvider->formattedValue()} | Lang: {$language->formattedValue()}]:", $output);
        $this->assertStringContainsString($this->expectedCommitMessage, $output);
        $this->assertStringContainsString('Success: No commit made.', $output);
    }

    public function test_it_should_use_provided_language_option_instead_of_default(): void
    {
        $this->mockFetchStagedChanges
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->diff);

        $this->configureObserverTraitMethods(
            $this->once(),
            $this->never(),
        );

        $this->mockGenerateCommitMessage
            ->expects($this->once())
            ->method('execute')
            ->with(
                $this->isString(),
                $this->equalTo($this->diff)
            )
            ->willReturn(<<<COMMIT_MESSAGE
            ```
            $this->expectedCommitMessage
            ```
            COMMIT_MESSAGE);

        $this->mockCommitStagedChanges
            ->expects($this->never())
            ->method('execute');

        $aiProvider = AiProvider::parse($this->userConfiguration->getValue('default_ai_provider'));
        $language = Language::PT_BR;

        $tester = new CommandTester($this->app->find('generate'));
        $tester->setInputs(['n']);
        $tester->execute([
            '--lang' => $language->value,
        ]);

        $output = $tester->getDisplay();

        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString("Below is the generated commit message [AI: {$aiProvider->formattedValue()} | Lang: {$language->formattedValue()}]:", $output);
        $this->assertStringContainsString($this->expectedCommitMessage, $output);
        $this->assertStringContainsString('Success: No commit made.', $output);
    }
}
