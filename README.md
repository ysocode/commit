# Commit - Automate your Conventional Commit messages with AI

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ysocode/commit.svg?style=flat)](https://packagist.org/packages/ysocode/commit)
[![Downloads on Packagist](https://img.shields.io/packagist/dt/ysocode/commit.svg?style=flat)](https://packagist.org/packages/ysocode/commit)

## Introduction

Commit is a PHP package that automates the generation of Conventional Commit messages using AI.  
By analyzing the `git diff`, it generates clear, structured commit messages that adhere to the
Conventional Commit standard.

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

##### Set the AI provider

To define which AI provider should be used:

```shell
commit ai:provider --set
```

An interactive prompt will appear, allowing you to select the desired AI provider.

##### Get the current AI provider

To retrieve the current AI provider:

```shell
commit ai:provider --get
```

#### Managing API Keys

##### Set your API key for a specific AI provider

Commit allows you to set your API keys for the AI providers you choose.
To set your API key for an AI provider, use the following commands:

```shell
commit ai:key --set YOUR_API_KEY --provider=openai
```

or

```shell
commit ai:key --set YOUR_API_KEY
```

If you don't specify a provider, an interactive prompt will appear with a list of available
AIs for you to select from.

##### Get the current API key for a specific AI provider

Retrieve the currently stored API key for the specified AI provider:

```shell
commit ai:key --get --provider=openai
```

or

```shell
commit ai:key --get
```

If you don't specify a provider, an interactive prompt will appear with a list of available
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
commit ai:key --set YOUR_API_KEY --provider=sourcegraph
```

The key you set here corresponds directly to the SRC_ACCESS_TOKEN used by the Cody CLI.

#### Main functionality

##### Generate a Commit Message

To automatically generate a Conventional Commit message based on the current Git diff:

```shell
commit
```

or

```shell
commit generate
```

This will display the generated commit message for your confirmation before finalizing the commit.

##### Using options to customize the commit

Use the `--provider` option to select the AI provider:

```shell
commit --provider=openai
```

Use the --lang option to specify the language for the commit message:

```shell
commit --lang=pt_br
```

Available languages:

- `pt_br` for Portuguese (Brazil)
- `en` for English

## License

Commit is open-sourced software licensed under the [MIT license](LICENSE.md).
