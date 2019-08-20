<?php

define("DIR", __DIR__ . '/');

require_once "vendor/autoload.php";
require_once "inc/autoload.php";

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\BrowserConsoleHandler;

use Inc\ESPNParser;

// Setting up fancy error reporting
$whoops = new \Whoops\Run;
$whoops->prependHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

// Setting up monolog
$log = new Logger('log');
$log->pushHandler(new BrowserConsoleHandler(Logger::DEBUG));

$parser = new ESPNParser("http://robin.local/dummy.html", $log);

echo $parser->getHomeTeamName(ESPNParser::ABBR_NAME);
echo "<br/>";
echo $parser->getAwayTeamName(ESPNParser::ABBR_NAME);