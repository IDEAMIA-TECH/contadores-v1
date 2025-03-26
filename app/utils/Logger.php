<?php

class Logger {
    private static $logFile = 'app/logs/app.log';

    public static function init() {
        $logDir = dirname(self::$logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    public static function debug($message, array $context = []) {
        self::log('DEBUG', $message, $context);
    }

    public static function info($message, array $context = []) {
        self::log('INFO', $message, $context);
    }

    public static function error($message, array $context = []) {
        self::log('ERROR', $message, $context);
    }

    private static function log($level, $message, array $context = []) {
        $date = date('d-M-Y H:i:s T');
        $contextStr = empty($context) ? '' : "\n" . print_r($context, true);
        $logMessage = "[$date] $level: $message$contextStr\n";
        
        error_log($logMessage, 3, self::$logFile);
    }
} 