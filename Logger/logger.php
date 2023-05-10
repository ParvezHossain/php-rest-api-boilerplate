 

<?php

/* This is a PHP class called Logger that is used for logging messages and data to a file.

The class has a static method get_logger() which can be used to get a singleton instance of the class. The method accepts an optional parameter $log_path which specifies the path where the log files will be stored. If $log_path is not provided, the default path is __DIR__ . '/logs'.

The class has two private static properties $logger and $user_logger which are used to store instances of the class. $logger is used for logging general messages, while $user_logger is used for logging user-specific messages.

The constructor of the class accepts the optional $log_path parameter and initializes a log file handle. If $log_path is not provided, it creates a log file in the default path with the format Y-m-d.log. If $log_path is provided, it creates a log file in that path with the same format.

The class has three public methods:

log($string) logs a message to the log file with a timestamp in the format Y-m-d D H:i:s.
dump($var_name, $string) logs a variable with a label in the format {LABEL} : {VARIABLE_DATA}. The label is converted to uppercase.
do_write($log_string) is a helper function that writes the log string to the log file handle.
The Logger class uses the constant DATESTRING_FULL which is a string in the format Y-m-d D H:i:s. This is used for formatting the timestamps in the log messages. */


define("DATESTRING_FULL", "Y-m-d D H:i:s");

/**
 * Logger - A simple logging class
 */
class Logger
{
    private static $logger;
    private static $userLogger;

    private bool $userHandle = false;

    /* The ?resource type hint in the class property means that the property can hold either a valid resource type or a null value. The resource type represents a resource handle that is returned by certain functions like fopen(), curl_init(), etc. The ? before the resource type hint indicates that the property is nullable, meaning that it can hold a null value in addition to a resource type.
In other words, $logHandle can either hold a resource type, which is a valid resource handle returned by a function like fopen(), or a null value, which indicates that the resource handle is not yet initialized.
*/
    private $logHandle = null;
    private $userLogHandle = null;

    /**
     * Get the singleton logger instance
     *
     * @param string|null $logPath The path to the log file for user-defined logging (optional)
     * @return Logger The logger instance
     */
    public static function getLogger(?string $logPath = null): Logger
    {
        if (is_null($logPath)) {
            if (!isset(self::$logger)) {
                self::$logger = new self();
            }
            return self::$logger;
        } else {
            if (!isset(self::$userLogger)) {
                self::$userLogger = new self($logPath);
                self::$userLogger->userHandle = true;
            }
            return self::$userLogger;
        }
    }

    /**
     * Constructor - Creates a new logger instance
     *
     * @param string|null $logPath The path to the log file for user-defined logging (optional)
     */
    private function __construct(?string $logPath = null)
    {
        if (is_null($logPath)) {
            $defaultLogPath = __DIR__ . '/logs';

            if (!file_exists($defaultLogPath)) {
                mkdir($defaultLogPath);
            }

            $logFileName = $defaultLogPath . '/' . date('Y-m-d') . '.log';
            $this->logHandle = fopen($logFileName, 'a');
        } else {
            if (!file_exists($logPath)) {
                mkdir($logPath, 0777, true);
            }

            $logFileName = $logPath . '/' . date('Y-m-d') . '.log';
            $this->userLogHandle = fopen($logFileName, 'a');
        }
    }

    /**
     * Log a message to the logger
     *
     * @param string $string The message to log
     */
    public function log(string $string): void
    {
        $this->write("\n" . '[' . date(DATESTRING_FULL) . '] : ' . $string);
    }

    /**
     * Dump a variable to the logger
     *
     * @param mixed $varName The variable to dump
     * @param string $label The label to use for the dump (optional)
     */
    public function dump($varName, string $label = 'VARDUMP'): void
    {
        $this->write("\n" . '[' . date(DATESTRING_FULL) . '] : {' . strtoupper($label) . '} : ' . var_export($varName, true));
    }

    /**
     * Write a message to the logger
     *
     * @param string $logString The message to write to the logger
     */
    private function write(string $logString): void
    {
        if ($this->userHandle) {
            fwrite($this->userLogHandle, $logString);
        } else {
            fwrite($this->logHandle, $logString);
        }
/* 
// Include the Logger class file
require_once 'path/to/Logger.php';

// Get the logger object with default log path
$logger = Logger::getLogger();

// Log a message
$logger->log('Hello, world!');

// Dump a variable
$myVar = array('foo' => 'bar');
$logger->dump($myVar, 'My Array');

// Get the logger object with custom log path
$customLogger = Logger::getLogger('path/to/custom/log/dir');

// Log a message to the custom log file
$customLogger->log('This message is logged to a custom log file'); 
*/
    }
}
