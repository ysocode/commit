# Commit - Automate your Conventional Commit messages with AI

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ysocode/commit.svg?style=flat)](https://packagist.org/packages/ysocode/commit)
[![Downloads on Packagist](https://img.shields.io/packagist/dt/ysocode/commit.svg?style=flat)](https://packagist.org/packages/ysocode/commit)

## Introduction

Commit is a PHP package that automates the generation of Conventional Commit messages using AI.  
By analyzing the `git diff`, it generates clear, structured commit messages that follow the Conventional Commit standard.

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

##### Set default AI provider

To define which AI provider should be used by default:
```shell
commit ai --set
```

##### Get default AI provider

To check the currently selected AI provider:
```shell
commit ai --get
```

##### Generate a Commit Message

To automatically generate a Conventional Commit message based on the current Git diff:
```shell
commit
```
or
```shell
commit generate
```
This will display the generated commit message for confirmation before finalizing the commit.

#### Managing API Keys

##### Set your API key for a specific AI provider

Commit allows you to set and manage API keys for the AI providers you choose.
To set or manage your API key for the selected provider, use the following command:
```shell
commit <provider>:key --set YOUR_API_KEY
```

##### Get the current API key for a specific AI provider

Retrieve the currently stored API key for the specified AI provider:
```shell
commit <provider>:key --get
```
This command retrieves the currently stored API key for the specified AI provider.

## License

Commit is open-sourced software licensed under the [MIT license](LICENSE.md).
