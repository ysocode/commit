<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use YSOCode\Commit\Application\Actions\FetchStagedGitChanges;
use YSOCode\Commit\Application\Actions\GetDefaultAiProviderFromUserConfiguration;
use YSOCode\Commit\Application\Console\GenerateConventionalCommitMessage\GenerateConventionalCommitMessage;

class GenerateConventionalCommitMessageTest extends TestCase
{
    use WithConsoleConfigurationTrait;

    private string $diff;

    protected function setUp(): void
    {
        $this->diff = <<<'DIFF'
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

        $this->setUpConsoleConfiguration();
        $this->app->add(
            new GenerateConventionalCommitMessage(
                new FetchStagedGitChanges,
                new GetDefaultAiProviderFromUserConfiguration($this->userConfiguration),
            )
        );
    }

    public function test_it_should_generate_conventional_commit_message_based_on_staged_changes(): void
    {
        $this->userConfiguration->setValue('default_ai_provider', 'sourcegraph');

        $tester = new CommandTester($this->app->find('generate'));
        $tester->execute([
            '--diff' => $this->diff,
        ]);

        $tester->assertCommandIsSuccessful();
    }
}
