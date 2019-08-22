<?php

define("DIR", __DIR__ . '/');

require_once "vendor/autoload.php";
require_once "inc/autoload.php";

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\BrowserConsoleHandler;

use Robin\ESPNParser;

// Setting up fancy error reporting
$whoops = new \Whoops\Run;
$whoops->prependHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

// Setting up monolog
$log = new Logger('log');
$log->pushHandler(new BrowserConsoleHandler(Logger::DEBUG));

try {
    $parser = new ESPNParser("http://robin.firstandgoal.in/dummy.html", $log);
    
    echo "<pre>";
    echo $parser->getHomeTeamName(ESPNParser::FULL_NAME).PHP_EOL;
    echo $parser->getAwayTeamName(ESPNParser::FULL_NAME).PHP_EOL;
    print_r($parser->getAllLeaders());
    echo "</pre>";

} catch (Exception $e) {
    echo "Caught exception: " .  $e->getMessage() . "\n";
}