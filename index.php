<?php
error_reporting(E_ALL); 

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


$url = [ "http://robin.firstandgoal.in/dummy.html",
         "https://www.espn.com/nfl/game/_/gameId/401128157",
         "https://www.espn.com/college-football/game/_/gameId/401110723",
         "https://www.espn.com/college-football/rankings",
         "https://www.espn.com/college-football/standings" ];
$url = $url[0];

try {
    $parser = new Parser($url);


    echo "<pre>";
//    echo $parser->page->getType();
    echo var_dump($parser->page->engine->getHomeTeam());
    echo var_dump($parser->page->engine->getAwayTeam());
    echo "</pre>";
/*
    echo $parser->engine->getHomeTeamName(0).PHP_EOL;
    echo $parser->engine->getAwayTeamName(0).PHP_EOL;
    print_r($parser->engine->getAllLeaders());
*/

} catch (Exception $e) {
    echo "Caught exception: " .  $e->getMessage() . "\n";
}