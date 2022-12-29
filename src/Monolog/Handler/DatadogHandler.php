<?php

namespace MonologDatadog\Handler;

use Exception;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Handler\Curl\Util;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\MissingExtensionException;
use MonologDatadog\Formatter\DatadogFormatter;
use CurlHandle;
/**
 * Sends logs to Datadog Logs using Curl integrations
 *
 * You'll need a Datzdog account to use this handler.
 *
 * @see https://docs.datadoghq.com/logs/ Datadog Logs Documentation
 * @author Gusp <contact@gusp.io>
 */
class DatadogHandler extends AbstractProcessingHandler
{
    /**
     * Datadog Api Key access
     *
     * @var string
     */
    protected const DATADOG_LOG_HOST = 'https://http-intake.logs.datadoghq.eu';

    /**
     * Datadog Api Key access
     *
     * @var string
     */
    private string $apiKey;

    /**
     * Datadog optionals attributes
     *
     * @var array
     */
    private array $attributes;

    /**
     * @param string     $apiKey     Datadog Api Key access
     * @param array      $attributes Some options fore Datadog Logs
     * @param string|int $level      The minimum logging level at which this handler will be triggered
     * @param bool       $bubble     Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct(
        string $apiKey,
        array $attributes = [],
        $level = Level::Debug,
        bool $bubble = true
    ) {
        if (!extension_loaded('curl')) {
            throw new MissingExtensionException('The curl extension is needed to use the DatadogHandler');
        }
        parent::__construct($level, $bubble);
        
        $this->apiKey     = $this->getApiKey($apiKey);
        $this->attributes = $attributes;
    }

    // /**
    //  * Loads and returns the shared curl handler for the given endpoint.
    //  */
    // protected function getCurlHandler(string $endpoint): CurlHandle
    // {
    //     if (!array_key_exists($endpoint, $this->curlHandlers)) {
    //         $this->curlHandlers[$endpoint] = $this->loadCurlHandle($endpoint);
    //     }

    //     return $this->curlHandlers[$endpoint];
    // }

    //     /**
    //  * Starts a fresh curl session for the given endpoint and returns its handler.
    //  */
    // private function loadCurlHandle(string $endpoint): CurlHandle
    // {
    //     $url = self::DATADOG_LOG_HOST . '/v1/input/';
    //     $url .= $this->apiKey;
    //     $url .= '?ddsource=' . $source . '&service=' . $service . '&hostname=' . $hostname . '&ddtags=' . $tags;

    //     $ch = curl_init();

    //     curl_setopt($ch, CURLOPT_URL, $url);
    //     curl_setopt($ch, CURLOPT_POST, true);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    //     return $ch;
    // }
    /**
     * @inheritDoc
     */
    protected function write(LogRecord $record): void
    {
        $this->send($record);
    }

    /**
     * Send request to @link https://http-intake.logs.datadoghq.com on send action.
     * @param LogRecord $record
     */
    protected function send(LogRecord $record): void
    {
        $headers = ['Content-Type:application/json'];

        $source   = $this->getSource();
        $hostname = $this->getHostname();
        $service  = $this->getService($record);
        $tags     = $this->getTags($record);

        $url = self::DATADOG_LOG_HOST . '/v1/input/';
        $url .= $this->apiKey;
        $url .= '?ddsource=' . $source . '&service=' . $service . '&hostname=' . $hostname . '&ddtags=' . $tags;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $record->formatted);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        Util::execute($ch);
    }

    /**
     * Get Datadog Api Key from $attributes params.
     * @param string $apiKey
     *
     * @return string
     */
    protected function getApiKey(string $apiKey): string
    {
        if ($apiKey) {
            return $apiKey;
        } else {
            throw new Exception('The Datadog Api Key is required');
        }
    }

    /**
     * Get Datadog Source from $attributes params.
     *
     * @return string
     */
    protected function getSource(): string
    {
        return $this->attributes['source'] ?? 'php';
    }

    /**
     * Get Datadog Service from $attributes params.
     * @param LogRecord $record
     *
     * @return string
     */
    protected function getService(LogRecord $record): string
    {
        return $this->attributes['service'] ?? $record->extra['channel'];
    }

    /**
     * Get Datadog Hostname from $attributes params.
     *
     * @return string
     */
    protected function getHostname(): string
    {
        return $this->attributes['hostname'] ?? $_SERVER['SERVER_NAME'];
    }

    /**
     * Get Datadog Tags from $attributes params.
     * @param LogRecord $record
     *
     * @return string
     */
    protected function getTags(LogRecord $record): string
    {
        $defaultTag = 'level:' . $record->level->getName();

        if (!isset($this->attributes['tags']) || !$this->attributes['tags']) {
            return $defaultTag;
        }

        if (
            (is_array($this->attributes['tags']) || is_object($this->attributes['tags']))
            && !empty($this->attributes['tags'])
        ) {
            $imploded = implode(',', (array) $this->attributes['tags']);

            return "{$imploded},{$defaultTag}";
        }

        return $defaultTag;
    }

    
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new DatadogFormatter();
    }
}
