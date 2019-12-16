<?php
/*
 * Use trait Logger to enable logging method for any object. If there is a global
 * variable $logger of Monolog\logger, it is used to log, otherwise, it just
 * collects messages in public array $logger;
 *
 * To add logging method add `use Logger` in class description and then use
 * $this->log(message) method.
 *
 */

namespace Robin;

trait Logger
{
    public $logger = false;
    
    public function __construct()
    {
        global $logger;
        
        $this->logger = $logger;
        
        if (!is_object($logger) || get_class($logger) != "Monolog\Logger") {
            $this->logger = [];
        }
    }
    
    public function log($message): void
    {
        if (!(is_string($message) || is_numeric($message))) {
            ob_start();
            var_dump($message);
            $message = ob_get_clean();
        }
        
        if (is_array($this->logger)) {
           $this->logger[] = $message;
           return;
        }
        
        if (is_object($this->logger) && get_class($this->logger) == "Monolog\Logger") {
            $this->logger->notice($message);
            return;
        } else {
            global $logger;
            
            if (is_object($logger) && get_class($logger) == "Monolog\Logger") {
                $this->logger = $logger;
                $this->logger->notice($message);
            }
        }
    }
}