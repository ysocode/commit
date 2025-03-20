<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use YSOCode\Commit\Application\Actions\CommitGitStagedChanges;
use YSOCode\Commit\Application\Actions\FetchStagedGitChanges;
use YSOCode\Commit\Application\Actions\GetDefaultAiProviderFromUserConfiguration;
use YSOCode\Commit\Application\Actions\GetDefaultLanguageFromUserConfiguration;
use YSOCode\Commit\Application\Console\GenerateConventionalCommitMessage\CommitStagedChangesInterface;
use YSOCode\Commit\Application\Console\GenerateConventionalCommitMessage\FetchStagedChangesInterface;
use YSOCode\Commit\Application\Console\GenerateConventionalCommitMessage\GenerateCommitMessageFactory;
use YSOCode\Commit\Application\Console\GenerateConventionalCommitMessage\GenerateCommitMessageInterface;
use YSOCode\Commit\Application\Console\GenerateConventionalCommitMessage\GenerateConventionalCommitMessage;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Enums\Language;
use YSOCode\Commit\Domain\Types\Error;

class GenerateConventionalCommitMessageTest extends TestCase
{
    use WithConfigurationToolsTrait, WithSymfonyConsoleApplicationTrait;

    private FetchStagedChangesInterface $mockFetchStagedChanges;

    private GenerateCommitMessageInterface $mockGenerateCommitMessage;

    private CommitStagedChangesInterface $mockCommitStagedChanges;

    private string $diff = <<<'DIFF'
        diff --git a/Example.php b/Example.php
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

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->setUpUserConfiguration();
        $this->setUpSymfonyConsoleApplication();

        $this->userConfiguration->setValue('default_ai_provider', 'sourcegraph');
        $this->userConfiguration->setValue('default_lang', 'en_US');

        $this->mockFetchStagedChanges = $this->createMock(FetchStagedGitChanges::class);
        $this->mockGenerateCommitMessage = $this->createMock(GenerateCommitMessageInterface::class);
        $mockGenerateCommitMessageFactory = $this->createMock(GenerateCommitMessageFactory::class);
        $this->mockCommitStagedChanges = $this->createMock(CommitGitStagedChanges::class);

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

    public function test_it_should_use_staged_changes_and_generate_message_when_no_diff_option_provided(): void
    {
        $this->mockFetchStagedChanges
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->diff);

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
                Error::parse('No changes found in the Git diff.')
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
        $this->assertStringContainsString('No changes found in the Git diff.', $output);
    }

    public function test_it_should_use_provided_ai_provider_option_instead_of_getting_default_ai_provider(): void
    {
        $this->mockFetchStagedChanges
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->diff);

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

    public function test_it_should_use_provided_language_option_instead_of_getting_default_language(): void
    {
        $this->mockFetchStagedChanges
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->diff);

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
