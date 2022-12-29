<?php

namespace MonologDatadog\Formatter;

use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Formatter\JsonFormatter;

class DatadogFormatter extends JsonFormatter
{
    /**
     * @var bool LogRecord $record
     */
    protected bool $includeStacktraces = true;

    /**
     * Map Monolog\Level levels to Datadog status type
     */
    private const DATADOG_LEVEL_MAP = [
        Level::Debug->value     => 'info',
        Level::Info->value      => 'info',
        Level::Notice->value    => 'warning',
        Level::Warning->value   => 'warning',
        Level::Error->value     => 'error',
        Level::Alert->value    => 'error',
        Level::Critical->value  => 'error',
        Level::Emergency->value => 'error',
    ];

    public function format(LogRecord $record): string
    {
        $normalized = $this->normalize($record);
        $r = $normalized->toArray();

        if (isset($r['context']) && $r['context']=== []) {
            $r['context'] = new \stdClass;
        }

        if (isset($r['extra']) && $r['extra'] === []) {
            $r['extra'] = new \stdClass;
        }
        $r['status'] = static::DATADOG_LEVEL_MAP[$record->level->value];
        return $this->toJson($normalized, true);
    }
}
