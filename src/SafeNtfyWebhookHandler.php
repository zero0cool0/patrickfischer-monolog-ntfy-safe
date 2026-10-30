<?php

namespace PatrickFischer\Monolog;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\SlackWebhookHandler;
use Monolog\Utils;
use Monolog\Handler\Slack\SlackRecord;
use Monolog\Level;
use Monolog\Handler\MissingExtensionException;
use Monolog\LogRecord;

class SafeNtfyWebhookHandler extends AbstractProcessingHandler
{
    private string $url;

    public function __construct(
        string $url,
        public string $topic,
        public string $title,
        public ?string $access_token = null,
        int|string|Level $level = Level::Error,
        bool $bubble = true,
        public string $tags = "warning,smiley",
        public int $timeout_ms = 500,
    ) {
        parent::__construct($level, $bubble);
        $this->topic = $topic;
        $this->url = rtrim($url, '/');
    }

    protected function write(LogRecord $record): void
    {
        $url = "{$this->url}/{$this->topic}";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $record['message']);

        // map monolog level to ntfy priority
        $priority = match ($record->level) {
            Level::Emergency, Level::Alert, Level::Critical => '5',
            Level::Error => '4',
            Level::Warning => '3',
            default => '2',
        };

        $headers = [
            "Title: {$this->title}",
            "Priority: {$priority}",
            "Tags: {$this->tags}"
        ];
        if ($this->access_token) {
            $headers = array_merge($headers, [
                "Authorization: Bearer {$this->access_token}",
            ]);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->timeout_ms);
        $curlResponse = curl_exec($ch);
        if ($curlResponse === false) {
            $curlErrno = curl_errno($ch);
            $curlError = curl_error($ch);
            error_log(sprintf('SafeNtfyWebhookHandler curl error (code %d): %s', $curlErrno, $curlError));
        }
        $json = json_decode($curlResponse);
        if (!$json || !isset($json->id)) {
            error_log("SafeNtfyWebhookHandler ntfy server retuned invalid response: {$curlResponse}");
        }
    }
}
