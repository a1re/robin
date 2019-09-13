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
//$logger->pushHandler(new StreamHandler('logs/dev.log', Logger::DEBUG));

$url = [ "http://robin.firstandgoal.in/dummy.html",
         "https://www.espn.com/nfl/game/_/gameId/401128157",
         "https://www.espn.com/college-football/game/_/gameId/401110723",
         "https://www.espn.com/college-football/game/_/gameId/401117856",
         "https://www.espn.com/college-football/game/_/gameId/401012356", // [4] LSU vs Texas A&M game (7 ovetimes)
         "https://www.espn.com/nfl/game/_/gameId/401030824", // [5] Broncos vs Chargers (defensive 2 points)
         "https://www.espn.com/nfl/game/_/gameId/330203025", // [6] 49ers vs Ravens
         "https://www.espn.com/nfl/game/_/gameId/401128173", // [7] Browns vs Lions (safety)
         "https://www.espn.com/nfl/game/_/gameId/401030952", // [8] Giants vs Buccaneers
         "https://www.espn.com/nfl/game/_/gameId/401030950", // [9] 49ers vs Seahawks
         "https://www.espn.com/nfl/game/_/gameId/340202007", // [10] Broncos vs Seahawks (SB 2014)
         "https://www.espn.com/nfl/game/_/gameId/401030791", // [11] Texans vs Giants (double names)
         "https://www.espn.com/nfl/game/_/gameId/401128157", // [12] Bengals vs Giants (no brakets in descriptions)
         "https://www.espn.com/nfl/game/_/gameId/401128137", // [13] Eagles vs Ravens (run two-point conversion)
         "https://www.espn.com/nfl/game/_/gameId/401030874", // [14] Cheifs vs Chargers (pass two-point conversion)
         "https://www.espn.com/nfl/game/_/gameId/401030972", // [15] Rams vs Chiefs (PAT Failed)
         "https://www.espn.com/nfl/game/_/gameId/401030917", // [16] Seahawks vs Chiefs (run two-point conversion))
         "https://www.espn.com/college-football/rankings",
         "https://www.espn.com/college-football/standings" ];
$url = $url[15];

try {
    $parser = new Parser($url);
    
    $home_team = $parser->page->engine->getHomeTeam();
    $away_team = $parser->page->engine->getAwayTeam();

    echo "<pre>";
    echo "Home team name: <strong>" . $home_team->short_name . "</strong> ";
    echo "(" . $home_team->abbr . ", " . $home_team->full_name . ")" . PHP_EOL;
    echo "Away team name: <strong>" . $away_team->short_name . "</strong> ";
    echo "(" . $away_team->abbr . ", " . $away_team->full_name . ")" . PHP_EOL;

    $home_team_passing_leader = $parser->page->engine->getHomePassingLeader();
    $home_team_rushing_leader = $parser->page->engine->getHomeRushingLeader();
    $home_team_receiving_leader = $parser->page->engine->getHomeReceivingLeader();
    $away_team_passing_leader = $parser->page->engine->getAwayPassingLeader();
    $away_team_rushing_leader = $parser->page->engine->getAwayRushingLeader();
    $away_team_receiving_leader = $parser->page->engine->getAwayReceivingLeader();

    echo PHP_EOL . "<strong>Home team leaders</strong>" . PHP_EOL;
    echo "Passing: " . $home_team_passing_leader->getClippedName() . " (";
    foreach ($home_team_passing_leader->getPassingStats() as $name => $value) {
        echo $value . " " . $name ." ";
    }
    echo ")" . PHP_EOL;
    echo "Rushing: " . $home_team_rushing_leader->getClippedName() . " (";
    foreach ($home_team_rushing_leader->getRushingStats() as $name => $value) {
        echo $value . " " . $name ." ";
    }
    echo ")" . PHP_EOL;
    echo "Receiving: " . $home_team_receiving_leader->getClippedName() . " (";
    foreach ($home_team_receiving_leader->getReceivingStats() as $name => $value) {
        echo $value . " " . $name ." ";
    }
    echo ")" . PHP_EOL;
    
    echo PHP_EOL . "<strong>Away team leaders</strong>" . PHP_EOL;
    echo "Passing: " . $away_team_passing_leader->getClippedName() . " (";
    foreach ($away_team_passing_leader->getPassingStats() as $name => $value) {
        echo $value . " " . $name ." ";
    }
    echo ")" . PHP_EOL;
    echo "Rushing: " . $away_team_rushing_leader->getClippedName() . " (";
    foreach ($away_team_rushing_leader->getRushingStats() as $name => $value) {
        echo $value . " " . $name ." ";
    }
    echo ")" . PHP_EOL;
    echo "Receiving: " . $away_team_receiving_leader->getClippedName() . " (";
    foreach ($away_team_receiving_leader->getReceivingStats() as $name => $value) {
        echo $value . " " . $name ." ";
    }
    echo ")" . PHP_EOL;
    
    echo PHP_EOL . "<strong>Scoring events</strong>" . PHP_EOL;
    $scoring_events = $parser->page->engine->getScoringEvents();
    foreach ($scoring_events as $e) {
        echo $e->quarter . " " . $e->method . "\t" . $e->getTeamAbbr() . "\t";
        if ($e->getAuthor() !== null) {
            echo $e->getAuthor() . " ";
        }
        echo $e->type . " ";
        if ($e->getPasser() !== null) {
            echo $e->getPasser() . " ";
        }
        if($e->isGood() == false) {
            echo "failed ";
        }
        echo "– " . $e->home_score . ":" . $e->away_score;
        echo PHP_EOL;
    }
    
    echo "</pre>";
/*
    echo $parser->engine->getHomeTeamName(0).PHP_EOL;
    echo $parser->engine->getAwayTeamName(0).PHP_EOL;
    print_r($parser->engine->getAllLeaders());
*/

} catch (Exception $e) {
    echo "Caught exception: " .  $e->getMessage() . " (Line " . $e->getLine() . " in " . $e->getFile() . "\n";
}