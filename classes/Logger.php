<?php
class Logger {
    private static $instance = null;
    private $logFile;
    private $levels = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3
    ];
    
    private function __construct() {
        $this->logFile = LOG_FILE;
        $this->ensureLogDirectory();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function ensureLogDirectory() {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    private function shouldLog($level) {
        $currentLevel = $this->levels[LOG_LEVEL] ?? 1;
        $messageLevel = $this->levels[$level] ?? 1;
        return $messageLevel >= $currentLevel;
    }
    
    private function log($level, $message, $context = []) {
        if (!$this->shouldLog($level)) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logEntry = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
        
        // Check file size and rotate if necessary
        $this->rotateLogIfNeeded();
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    private function rotateLogIfNeeded() {
        if (file_exists($this->logFile) && filesize($this->logFile) > LOG_MAX_SIZE) {
            $backupFile = $this->logFile . '.' . date('Y-m-d-H-i-s');
            rename($this->logFile, $backupFile);
        }
    }
    
    public static function debug($message, $context = []) {
        self::getInstance()->log('DEBUG', $message, $context);
    }
    
    public static function info($message, $context = []) {
        self::getInstance()->log('INFO', $message, $context);
    }
    
    public static function warning($message, $context = []) {
        self::getInstance()->log('WARNING', $message, $context);
    }
    
    public static function error($message, $context = []) {
        self::getInstance()->log('ERROR', $message, $context);
    }
    
    public static function logException($exception, $context = []) {
        $message = get_class($exception) . ': ' . $exception->getMessage() . 
                  ' in ' . $exception->getFile() . ':' . $exception->getLine();
        self::error($message, array_merge($context, ['trace' => $exception->getTraceAsString()]));
    }
    
    public static function logApiCall($method, $url, $requestData, $responseData, $duration) {
        $message = "API Call: $method $url";
        self::info($message, [
            'request' => $requestData,
            'response' => $responseData,
            'duration' => $duration . 'ms'
        ]);
    }
    
    public static function logOTASync($otaName, $syncType, $status, $message, $data = []) {
        $logMessage = "OTA Sync [$otaName] $syncType: $status - $message";
        self::info($logMessage, $data);
    }
    
    public static function logUserAction($userId, $action, $details = []) {
        $message = "User Action: User ID $userId performed $action";
        self::info($message, $details);
    }
    
    public static function logSecurity($event, $details = []) {
        $message = "Security Event: $event";
        self::warning($message, $details);
    }
    
    public function getRecentLogs($lines = 100) {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $logs = [];
        $handle = fopen($this->logFile, 'r');
        
        if ($handle) {
            // Read file from end
            fseek($handle, -1, SEEK_END);
            $line = '';
            $pos = ftell($handle);
            
            while ($pos >= 0 && count($logs) < $lines) {
                $char = fgetc($handle);
                if ($char === "\n") {
                    if (!empty($line)) {
                        $logs[] = strrev($line);
                    }
                    $line = '';
                } else {
                    $line .= $char;
                }
                fseek($handle, --$pos);
            }
            
            if (!empty($line)) {
                $logs[] = strrev($line);
            }
            
            fclose($handle);
        }
        
        return array_reverse($logs);
    }
    
    public function clearLogs() {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
    }
}
?>