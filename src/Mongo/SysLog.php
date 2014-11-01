<?php

namespace Reach\Mongo;

use MongoLog;

class SysLog extends MongoLog
{

    public function init($level = self::ALL, $module = self::ALL)
    {
        self::setLevel($level);
        self::setModule($module);
        self::setCallback([$this, 'callback']);
    }

    public function callback($module, $level, $message)
    {
        $module = $this->module2string($module);
        $level = $this->level2string($level);
        $message = date("Y-m-d H:i:s - ") . "$module ($level): $message\n";
        error_log($message);
    }

    protected function module2string($module)
    {
        switch ($module) {
            case MongoLog::RS:
                return "REPLSET";
            case MongoLog::CON:
                return "CON";
            case MongoLog::IO:
                return "IO";
            case MongoLog::SERVER:
                return "SERVER";
            case MongoLog::PARSE:
                return "PARSE";
            default:
                return "UNKNOWN";
        }
    }

    protected function level2string($level)
    {
        switch ($level) {
            case MongoLog::WARNING:
                return "WARN";
            case MongoLog::INFO:
                return "INFO";
            case MongoLog::FINE:
                return "FINE";
            default:
                var_dump($level);
                return "UNKNOWN";
        }
    }
}