# Commit - Automate your Conventional Commit messages with AI

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ysocode/commit.svg?style=flat)](https://packagist.org/packages/ysocode/commit)
[![Downloads on Packagist](https://img.shields.io/packagist/dt/ysocode/commit.svg?style=flat)](https://packagist.org/packages/ysocode/commit)

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
commit generate --provider=sourcegraph
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
