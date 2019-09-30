<?php
require_once "vendor/autoload.php";

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use Robin\Parser;
use Robin\ESPN\Event;

// Setting up fancy error reporting
$whoops = new \Whoops\Run;
$whoops->prependHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

// Setting up monolog
$logger = new Logger('log');
$logger->pushHandler(new StreamHandler('logs/dev.log', Logger::DEBUG));

header('Content-Type: application/json');
$data = [ ];

function status(int $code, string $message): array
{
    return ["status" => ["code" => $code, "message" => $message]];
}

if (!array_key_exists("get", $_GET)) {
    echo json_encode(status(400, "Please, specify request by adding get variable to header"));
    return;
}

if (!array_key_exists("url", $_GET)) {
    echo json_encode(status(400, "Please, specify URL to be parsed"));
    return;    
}

if(!filter_var($_GET["url"], FILTER_VALIDATE_URL)) {
    echo json_encode(status(406, "Provided URL is not valid"));
    return;
}

$lang = array_key_exists("lang", $_GET) ? $_GET["lang"] : "en";

try {
    if ($_GET["get"] == "gamecast/header") {
        $parser = new Parser($_GET["url"]);
        $parser->setLanguage($lang);
        
        $home_team = $parser->getHomeTeam();
        $away_team = $parser->getAwayTeam();
        $score = $parser->getScore();
        
        if ($home_team != null) {
            $data["home_team"] = [
                "full_name" => $home_team->full_name,
                "short_name" => $home_team->short_name,
                "abbr" => $home_team->abbr,
                "img" =>  $home_team->img,
                "is_translated" => $home_team->isTranslated()
            ];
        }
        
        if ($away_team != null) {
            $data["away_team"] = [
                "full_name" => $away_team->full_name,
                "short_name" => $away_team->short_name,
                "abbr" => $away_team->abbr,
                "img" =>  $away_team->img,
                "is_translated" => $away_team->isTranslated()
            ];
        }
        
        if (count($data) == 0) {
            echo json_encode(status(409, "No data was parsed"));
            return;
        }
        
        if ($datetime = $parser->getScheduleTime()) {
            $data["date"] = $datetime->format("d.m");
            $data["time"] = $datetime->format("H:i");
        } else {
            $data["date"] = null;
            $data["time"] = null;
        }
        
        if ($score == null) {
            $data["has_score"] = false;
        } else {
            $data["home_score"] = $score->home[0];
            $data["away_score"] = $score->away[0];
            $data["has_score"] = true;
        }
    } else if ($_GET["get"] == "gamecast/quarters") {
        $parser = new Parser($_GET["url"]);
        $parser->setLanguage($lang);
        
        $home_team = $parser->getHomeTeam();
        $away_team = $parser->getAwayTeam();
        
        if ($home_team == null || $away_team == null) {
            echo json_encode(status(409, "No data was parsed"));
            return;
        } else {
            $data["home"]["team"] = [
                "full_name" => $home_team->full_name,
                "short_name" => $home_team->short_name,
                "abbr" => $home_team->abbr,
                "img" =>  $home_team->img,
                "is_translated" => $home_team->isTranslated()
            ];
            $data["away"]["team"] = [
                "full_name" => $away_team->full_name,
                "short_name" => $away_team->short_name,
                "abbr" => $away_team->abbr,
                "img" =>  $away_team->img,
                "is_translated" => $away_team->isTranslated()
            ];
        }
        
        $quarters = $parser->getScore();
        
        if ($quarters == null) {
            $data["has_score"] = false;
        } else {
            
            $data["home"]["quarters"] = [
                "Q1" => $quarters->home[1],
                "Q2" => $quarters->home[2],
                "Q3" => $quarters->home[3],
                "Q4" => $quarters->home[4]
            ];
            
            if (array_key_exists(5, $quarters->home)) {
                $data["home"]["quarters"]["OT"] = $quarters->home[5];
            }
            
            $data["home"]["score"] = $quarters->home[0];
            
            $data["away"]["quarters"] = [
                "Q1" => $quarters->away[1],
                "Q2" => $quarters->away[2],
                "Q3" => $quarters->away[3],
                "Q4" => $quarters->away[4]
            ];
            
            if (array_key_exists(5, $quarters->away)) {
                $data["away"]["quarters"]["OT"] = $quarters->away[5];
            }
            
            $data["away"]["score"] = $quarters->away[0];
            $data["has_score"] = true;
        }
    } else if ($_GET["get"] == "gamecast/leaders") {
        $parser = new Parser($_GET["url"]);
        $parser->setLanguage($lang);
        
        $home_team = $parser->getHomeTeam();
        $away_team = $parser->getAwayTeam();
        
        $data["home_team"]["passing"] = $parser->getHomePassingLeader();
        $data["home_team"]["rushing"] = $parser->getHomeRushingLeader();
        $data["home_team"]["receiving"] = $parser->getHomeReceivingLeader();
        $data["away_team"]["passing"] = $parser->getAwayPassingLeader();
        $data["away_team"]["rushing"] = $parser->getAwayRushingLeader();
        $data["away_team"]["receiving"] = $parser->getAwayReceivingLeader();
        
        foreach ($data as $team => $stats_category) {
            foreach ($stats_category as $stats_category_name => $stats_leader) {
                if ($stats_leader != null) {
                    
                    $stats_leader_stats = [];
                    
                    if ($team == "home_team") {
                        $id_prefix = $home_team ? $home_team->getId() . "/" : "";
                    } else {
                        $id_prefix = $away_team ? $away_team->getId() . "/" : "";                        
                    }
                    
                    $stats_leader_stats["player"] = [
                        "first_name" => $stats_leader->first_name,
                        "last_name" => $stats_leader->last_name,
                        "is_translated" => $stats_leader->isTranslated(),
                        "id" => $id_prefix . $stats_leader->getId()
                    ];
                    
                    $method_name = "get" . ucfirst($stats_category_name) . "Stats";
                    
                    foreach ($stats_leader->{$method_name}() as $name => $value) {
                            $stats_leader_stats["stats"][] = $value . " " . $parser->i18n($name, $value);
                    }
                    
                    $data[$team][$stats_category_name] = $stats_leader_stats;
                }
            }
        }
    } else if ($_GET["get"] == "gamecast/events") {
        $parser = new Parser($_GET["url"]);
        $parser->setLanguage($lang);
        
        $home_team = $parser->getHomeTeam();
        $away_team = $parser->getAwayTeam();
        
        $scoring_events = $parser->getScoringEvents();
        foreach ($scoring_events as $event) {
            $data_event = [
                "quarter" => $event->quarter,
                "method" => $parser->i18n($event->method),
                "home_score" => $event->home_score,
                "away_score" => $event->away_score,
                "description" => $parser->i18n($event->type)
            ];
            
            if ($event->method == Event::XP) {
                $data_event["is_extra"] = 1;
            } else if ($event->method == Event::X2P) {
                $data_event["is_extra"] = 2;
            } else {
                $data_event["is_extra"] = 0;
            }
            
            $data_event["is_good"] = $event->isGood();
            $player_id_prefix = "";
            
            if ($event->team != null) {
                $data_event["team"] = [
                    "full_name" => $event->team->full_name,
                    "short_name" => $event->team->short_name,
                    "abbr" => $event->team->abbr,
                    "img" => $event->team->img,
                    "is_translated" => $event->team->isTranslated()
                ];
                $player_id_prefix = $event->team->getId() . "/";
            }
            
            if ($event->author != null) {
                $data_event["author"] = [
                    "first_name" => $event->author->first_name,
                    "last_name" => $event->author->last_name,
                    "first_name_genitive" => $event->author->first_name_genitive,
                    "last_name_genitive" => $event->author->last_name_genitive,
                    "position" => $event->author->position,
                    "number" => $event->author->number,
                    "is_translated" => $event->author->isTranslated(),
                    "id" => $player_id_prefix . $event->author->getId()
                ];
            }
            
            if ($event->passer != null) {
                $data_event["passer"] = [
                    "first_name" => $event->passer->first_name,
                    "last_name" => $event->passer->last_name,
                    "first_name_genitive" => $event->passer->first_name_genitive,
                    "last_name_genitive" => $event->passer->last_name_genitive,
                    "position" => $event->passer->position,
                    "number" => $event->passer->number,
                    "is_translated" => $event->passer->isTranslated(),
                    "id" => $player_id_prefix . $event->passer->getId()
                ];
            }
            
            $data[] = $data_event;
        }
    } else if ($_GET["get"] == "type") {
        $parser = new Parser($_GET["url"]);
        $parser->setLanguage($lang);
        
        $data["type"] = $parser->getType();
    } else {
        echo json_encode(status(501, "Unknown method"));
        return;    
    }
} catch (Exception $e) {
    echo json_encode(status(500, "Caught exception: " .  $e->getMessage() . " (Line " . $e->getLine() . " in " . $e->getFile() . ")\n"));
    return;
}

echo json_encode(array_merge(status(200, "ok"), ["response" => $data]));