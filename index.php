<?php

require_once "vendor/autoload.php";

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\BrowserConsoleHandler;

use Robin\Parser;

// Setting up fancy error reporting
$whoops = new \Whoops\Run;
$whoops->prependHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

// Setting up monolog
$logger = new Logger('log');
$logger->pushHandler(new BrowserConsoleHandler(Logger::DEBUG));


try {
    $parser = new Parser("http://robin.local/dummy.html");

    echo "<pre>";
    echo $parser->engine->home_team_full_name;
    echo "</pre>";
/*
    echo $parser->engine->getHomeTeamName(0).PHP_EOL;
    echo $parser->engine->getAwayTeamName(0).PHP_EOL;
    print_r($parser->engine->getAllLeaders());
*/

} catch (Exception $e) {
    echo "Caught exception: " .  $e->getMessage() . " (line: " . $e->getLine() .")\n";
}