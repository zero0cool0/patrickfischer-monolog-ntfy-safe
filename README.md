# SafeNtfyWebhookHandler

A non fatal Monolog handler for ntfy.sh which does not throw on curl error, but uses error_log() as a final fallback.

## Installation

Install it with composer:

```bash
composer require patrickfischer/monolog-ntfy-safe
```

Update `config/logging.php` to use the handler like so:

```
use PatrickFischer\Monolog\SafeNtfyWebhookHandler;

return [
    // other configuration

    'channels' => [
        // other channels

        'ntfy' => [
            'driver'  => 'monolog',
            'handler' => SafeNtfyWebhookHandler::class,
            'with' => [
                'url' => env('LOG:_NTFY_URL'),
                // Optional access token
                'access_token' => env('LOG_NTFY_TOKEN'),
                // Implement a hard to guess topic
                'topic' => sha1('Laravel'),
                'title' => env('LOG_NTFY_TITLE'),
                'level' => env('LOG_NTFY_LEVEL', 'error'),
                'timeout_ms' => 1000,
                'tags' => 'warning,php',
                'bubble' => true,
            ],
        ],
    ]
];
```



## License

MIT