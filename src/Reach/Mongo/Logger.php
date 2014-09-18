<?php

class Logger {

    private $_log = [];

    public function log($caller, $backtrace)
    {
        $log = '[' . date('r') . "] {$caller['file']}:{$caller['line']} -- \$mongo->{$caller['object']->getName()}->{$caller['function']}(";
        foreach ($caller['args'] as $arg) {
            if (!empty($arg)) {
                $log .= " " . json_encode($arg);
            }
        }
        $log .= " )";

        $this->_log[] = $log;
    }

    public function getLog()
    {
        return $this->_log;
    }

}