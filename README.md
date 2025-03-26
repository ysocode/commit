# Commit - Automate your Conventional Commit messages with AI

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ysocode/commit.svg?style=flat)](https://packagist.org/packages/ysocode/commit)
[![Downloads on Packagist](https://img.shields.io/packagist/dt/ysocode/commit.svg?style=flat)](https://packagist.org/packages/ysocode/commit)
[![License](https://img.shields.io/packagist/l/ysocode/commit)](https://packagist.org/packages/ysocode/commit)

## Introduction

Commit is a PHP package that automates the generation of Conventional Commit messages using AI.
By analyzing your `staged changes`, it generates clear and structured commit messages
that strictly follow the Conventional Commits standard.

## Official Documentation

##### Install Commit using Composer:

```shell
composer global require ysocode/commit
```

##### Initialize Configuration

To initialize the necessary configuration files:

```shell
commit init
```

#### Manage AI Provider

##### List all enabled AI providers

To display all enabled AI providers:

```shell
commit ai:provider --list
```

##### Set the default AI provider

To define which AI provider should be used as default:

```shell
commit ai:provider sourcegraph
```

If no provider argument is specified, an interactive prompt will appear, allowing you to select
the desired AI provider.

##### Get the current default AI provider

To display the current AI provider:

```shell
commit ai:provider --get
```

#### Managing API Keys

##### Set your API key for a specific AI provider

Commit allows you to set your API keys for the AI providers you choose.
To set your API key for an AI provider, use the following commands:

```shell
commit ai:key --provider=openai YOUR_API_KEY
```

If you don't specify a provider, an interactive prompt will appear with a list of enabled
AIs for you to select from.

##### Get the current API key for a specific AI provider

Retrieve the currently stored API key for the specified AI provider:

```shell
commit ai:key --get --provider=openai
```

If you don't specify a provider, an interactive prompt will appear with a list of enabled
AIs for you to select from.

##### Sourcegraph Provider

Sourcegraph is available as an AI provider in our list of supported services.
To use Sourcegraph, you must first install the Cody CLI:

```shell
npm install -g @sourcegraph/cody
```

For a comprehensive guide on Cody CLI installation and configuration, visit the
[Cody CLI documentation](https://sourcegraph.com/docs/cody/clients/install-cli).

Now that you have the Cody CLI available, you can follow the same process as other
AI providers by setting your key, and ysocode/commit will handle the rest for you:

```shell
commit ai:key --provider=sourcegraph YOUR_API_KEY
```

The key you set here corresponds directly to the SRC_ACCESS_TOKEN used by the Cody CLI.

#### Main functionality

##### Generate a Commit Message

To automatically generate a Conventional Commit message based on the current `staged changes`:

```shell
commit generate
```

This will display the generated commit message for your confirmation before finalizing the commit.

##### Using options to customize the commit

Use the `--provider` option to select the AI provider:

```shell
commit generate --provider=openai
```

Use the `--lang` option to specify the language for the commit message:

```shell
commit generate --lang=pt_BR
```

Enabled languages:

- `en_US` for English (United States)
- `pt_BR` for Portuguese (Brazil)
- `es_ES` for Spanish (Spain)

## License

Commit is open-sourced software licensed under the [MIT license](LICENSE.md).
