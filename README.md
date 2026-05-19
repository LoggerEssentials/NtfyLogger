# NtfyLogger

A small [PSR-3](https://www.php-fig.org/psr/psr-3/) compatible logger and client for publishing messages to [ntfy](https://ntfy.sh/).

## Requirements

- PHP >= 8.1
- A PSR-18 HTTP client
- PSR-17 request and stream factories

## Installation

Install the package with Composer:

```bash
composer require logger/ntfy
```

Install a PSR-18 client and PSR-17 implementation if your application does not already provide them. For example:

```bash
composer require symfony/http-client nyholm/psr7
```

## Basic Usage

```php
<?php

use Logger\Ntfy\NtfyClient;
use Logger\Ntfy\NtfyConfiguration;
use Logger\Ntfy\NtfyExceptionConfiguration;
use Logger\Ntfy\NtfyLogger;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\Psr18Client;

$psr17 = new Psr17Factory();

$client = new NtfyClient(
	client: new Psr18Client(),
	requestFactory: $psr17,
	streamFactory: $psr17,
	config: new NtfyConfiguration(
		topic: 'my-alerts',
		token: 'tk_your_access_token',
	),
);

$logger = new NtfyLogger($client);

$logger->info('Import finished for order {orderId}', [
	'orderId' => 12345,
]);

$logger->error('Background job failed', [
	'title' => 'Worker error',
	'tags' => ['warning', 'computer'],
	'click' => 'https://example.com/jobs/12345',
]);
```

## Configuration

`NtfyConfiguration` accepts the target topic, optional authentication, and the ntfy server URL:

```php
new NtfyConfiguration(
	topic: 'my-alerts',
	token: 'tk_your_access_token',
	serverUrl: 'https://ntfy.sh',
	exceptionConfiguration: new NtfyExceptionConfiguration(
		basePath: __DIR__,
		applicationPaths: ['src', 'modules'],
	),
);
```

For self-hosted ntfy instances, pass your own server URL:

```php
new NtfyConfiguration(
	topic: 'ops-alerts',
	serverUrl: 'https://ntfy.example.com',
	username: 'logger',
	password: 'secret',
);
```

Use either bearer-token authentication or username/password authentication. Configuring both will throw an `InvalidArgumentException`.

## Sending Messages Directly

You can use `NtfyClient` without the PSR-3 logger:

```php
use Logger\Ntfy\NtfyParams;

$client->sendMessage('Deployment finished', new NtfyParams(
	title: 'Production',
	priority: 3,
	tags: ['rocket'],
	click: 'https://example.com/deployments/42',
));
```

## Passing ntfy Parameters

The logger maps common context keys to ntfy headers:

```php
$logger->warning('Queue depth is high', [
	'title' => 'Queue warning',
	'priority' => 3,
	'tags' => ['triangular_flag_on_post'],
	'click' => 'https://example.com/queues',
	'url' => 'https://example.com/queues',
	'ntfy_url' => 'https://example.com/queues',
	'topic' => 'ops-alerts',
	'sequence_id' => 'queue-depth',
]);
```

`url` and `ntfy_url` are aliases for the notification click URL. When both are present, `ntfy_url` wins.

## Exception Context

When the context contains an `exception` value and that value is a `Throwable`, the logger appends a compact Markdown exception report to the notification body and enables ntfy Markdown rendering.

```php
$logger->error('Import failed', [
	'exception' => $exception,
]);
```

The report renders the exception class, message, throw location, and stack trace in short line-based blocks so it remains readable on narrow phone screens. Files that belong to the application are highlighted in bold.

Configure exception path handling through `NtfyExceptionConfiguration`:

```php
$config = new NtfyConfiguration(
	topic: 'ops-alerts',
	exceptionConfiguration: new NtfyExceptionConfiguration(
		basePath: '/var/www/app',
		applicationPaths: ['src', 'modules'],
	),
);
```

`basePath` is used to shorten absolute file names in the trace. `applicationPaths` defines which files should be highlighted. Relative application paths are resolved below `basePath`.

For full control, pass an `NtfyParams` instance or an array under the `ntfy` context key:

```php
$logger->critical('Database unavailable', [
	'ntfy' => [
		'title' => 'Database outage',
		'priority' => 5,
		'tags' => ['rotating_light'],
		'markdown' => true,
		'actions' => [
			[
				'action' => 'view',
				'label' => 'Open status page',
				'url' => 'https://status.example.com',
			],
		],
	],
]);
```

Supported parameter keys are:

- `title`
- `priority`
- `tags`
- `markdown`
- `click`
- `attach`
- `filename`
- `icon`
- `actions`
- `delay`
- `email`
- `call`
- `topic`
- `sequence_id`
- `cache`
- `firebase`
- `unified_push`

## Log Level Defaults

When no custom ntfy parameters are provided, the logger derives priority and tags from the PSR-3 level:

| PSR-3 level | ntfy priority | Default tags |
| --- | ---: | --- |
| `emergency`, `alert`, `critical` | 5 | `rotating_light` |
| `error` | 4 | `warning` |
| `warning` | 3 | `triangular_flag_on_post` |
| `notice`, `info` | 2 | `information_source` |
| `debug` and other levels | 1 | none |

The default notification title is the uppercase log level, unless a `title` context value is provided.

## Error Handling

`NtfyClient::sendMessage()` throws a `RuntimeException` when ntfy responds with a non-2xx HTTP status code. PSR-18 transport errors are forwarded from the configured HTTP client.

## Testing

Run the PHPUnit suite with Composer:

```bash
composer run test
```

## License

Proprietary.
