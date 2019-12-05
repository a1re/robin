<?php

namespace Robin\ESPN;

use \Exception;
use \Robin\Logger;
use \Robin\Player;
use \Robin\Team;
use \Robin\Drive;
use \Robin\GameTerms;
use \Robin\Inflector;
use \Robin\FileHandler;
use \Robin\ESPN\Parser;

class Gamecast
{
    use Logger;
    
    const METHODS = [ "header", "quarters", "leaders", "scoring" ];
    const TEAMS_LIST = [ "home", "away" ];
    const LEADERS_LIST = [ "passing_leader", "rushing_leader", "receiving_leader" ];
    const TIMEZONE = "Europe/Moscow";
    
    private $schedule_time, $score, $home_team, $away_team, $home_passing_leader,
    $away_passing_leader, $home_rushing_leader, $away_rushing_leader,
    $home_receiving_leader, $away_receiving_leader;
    private $drives = [ ];
    
    /**
     * Class constructor
     *
     * @param   string  $url            URL of the page to get info
     * @param   string  $language       Source language of the page
     * @param   string  $locale         (optional) Locale of the parsed data
     */
    public function __construct($url, string $language, string $locale = "")
    {
        if (is_array($url)) {
            $this->import($url);
            return;
        }
        
        $parser = new Parser($url, $language);
        if(strlen($locale) > 0 && $locale != $language) {
            $parser->setLocale($locale);
        }
        
        $this->schedule_time = $parser->getScheduleTime(self::TIMEZONE);
        $this->score = $parser->getScore();
        
        foreach (self::TEAMS_LIST as $team) {
            $team_name = $team . "_team";
            $method_name = "get" . ucfirst($team) . "Team";
            $this->$team_name = $parser->$method_name();
            $team_id = trim($this->$team_name->full_name);
            $this->$team_name->setId($team_id);
            foreach (self::LEADERS_LIST as $leader) {
                $leader_name = $team . "_" . $leader;
                $method_name = "get" . ucfirst(Inflector::underscoreToCamelCase($leader_name));
                $this->$leader_name = $parser->$method_name();
                $leader_id = $this->$team_name->getId() . "/" . $this->$leader_name->first_name . " " . $this->$leader_name->last_name;
                $this->$leader_name->setId($leader_id);
            }
        }
        
        $this->drives = $parser->getScoringDrives();
    }
    
    /**
     * Returns type of the object
     *
     * @return  string        Object type name
     */
    public function getType(): string
    {
        return "Gamecast";
    }
    
    /**
     * List of public methods available for calling
     *
     * @return  array   List of methods
     */
    public function getMethods(): array
    {
    	return self::METHODS;
    }
    
    public function export(): array
    {
        $export = [ ];
        $export["schedule_time"] = $this->schedule_time->format("c");
        $export["score"] = $this->score;
        
        foreach (self::TEAMS_LIST as $team) {
            $team_name = $team . "_team";
            $export[$team_name] = $this->$team_name->export();
            foreach (self::LEADERS_LIST as $leader) {
                $leader_name = $team . "_" . $leader;
                $export[$leader_name] = $this->$leader_name->export(true);
            }
        }
        
        foreach ($this->drives as $drive) {
            $export["drives"][] = $drive->export();
        }
        
        return $export;
    }
    
    public function import(array $values): void
    {
        if (!array_key_exists("schedule_time", $values)) {
            throw new Exception("Import array lacks 'schedule_time' value");
        }
        
        $this->schedule_time = new \DateTime($values["schedule_time"]);
        $this->schedule_time->setTimezone(new \DateTimeZone(self::TIMEZONE));
        
        if (!(array_key_exists("score", $values) && is_array($values["score"]))) {
            throw new Exception("Import array lacks 'score' value");
        }
        $this->score = $values["score"];
        
        foreach (self::TEAMS_LIST as $team) {
            $team_name = $team . "_team";
            if (!array_key_exists($team_name, $values) || !is_array($values[$team_name])) {
                throw new Exception("Import array lacks '" . $team_name . "' value");
            }
            $this->$team_name = new Team($values[$team_name]);
            $this->$team_name->setId($this->$team_name->getFullName());
            foreach (self::LEADERS_LIST as $leader) {
                $leader_name = $team . "_" . $leader;
                if (!array_key_exists($leader_name, $values) && !is_array($values[$leader_name])) {
                    throw new Exception("Import array lacks '" . $leader_name . "' value");
                }
                
                $this->$leader_name = new Player($values[$leader_name]);
                $leader_name_id = $this->$team_name->getFullName() . "/" . $this->$leader_name->getFullName();
                $this->$leader_name->setId($leader_name_id);
            }
        }
        
        if (!array_key_exists("drives", $values) || !is_array($values["drives"])) {
            throw new Exception("Import array lacks 'drives' value");
        }
        
        foreach ($values["drives"] as $drive_export){
            if (is_array($drive_export) && $drive = new Drive($drive_export)) {
                $this->drives[] = $drive; 
            }
        }
    }
    
    public function header(): array
    {
        $home_team = [
            "name" => $this->home_team->getShortName(),
            "full_name" => $this->home_team->getFullName(),
            "abbr" => $this->home_team->getAbbr(),
            "logo" => $this->home_team->getImg(),
            "logo_width" => 150,
            "logo_height" => 150,
            "score" => $this->score["home"][0]
        ];
        
        $away_team = [
            "name" => $this->away_team->getShortName(),
            "full_name" => $this->away_team->getFullName(),
            "abbr" => $this->away_team->getAbbr(),
            "logo" => $this->away_team->getImg(),
            "logo_width" => 150,
            "logo_height" => 150,
            "score" => $this->score["away"][0]
        ];
        
        return [ "home_team" => $home_team, "away_team" => $away_team ];
    }
    
    public function quarters(): array
    {
        $home_score = [
            "Q1" => $this->score["home"][1],
            "Q2" => $this->score["home"][2],
            "Q3" => $this->score["home"][3],
            "Q4" => $this->score["home"][4],
        ];
        
        if (array_key_exists(5, $this->score["home"])) {
            $home_score["OT"] = $this->score["home"][5];
        } else {
            $home_score["OT"] = null;
        }
        
        $home_score["TOTAL"] = $this->score["home"][0];
        
        $away_score = [
            "Q1" => $this->score["away"][1],
            "Q2" => $this->score["away"][2],
            "Q3" => $this->score["away"][3],
            "Q4" => $this->score["away"][4],
        ];
        
        if (array_key_exists(5, $this->score["away"])) {
            $away_score["OT"] = $this->score["away"][5];
        } else {
            $away_score["OT"] = null;
        }
        
        $away_score["TOTAL"] = $this->score["away"][0];
        
        return [ "home" => $home_score, "away" => $away_score ];
    }
    
    public function leaders(): array
    {
        $leaders = [
            "home_team" => [
                "name" => $this->home_team->getShortName(),
                "full_name" => $this->home_team->getFullName(),
                "abbr" => $this->home_team->getAbbr(),
                "logo" => $this->home_team->getImg()
            ],
            "away_team" => [
                "name" => $this->away_team->getShortName(),
                "full_name" => $this->away_team->getFullName(),
                "abbr" => $this->away_team->getAbbr(),
                "logo" => $this->away_team->getImg()
            ]
        ];
        foreach (self::LEADERS_LIST as $leader) {
            $leaders[$leader] = [ ];
            foreach (self::TEAMS_LIST as $team) {
                $leader_name = $team . "_" . $leader;
                $leaders[$leader][$team . "_team"] = [
                    "first_name" => $this->$leader_name->getFirstName(),
                    "last_name" =>$this->$leader_name->getLastName(),
                    "stats" => $this->$leader_name->getJoinedStats(", ")
                ];
            }
        }
        return $leaders;
    }
    
    public function scoring(): array
    {
        $drives = [ ];
        
        foreach ($this->drives as $event) {
            $plays = $event->getPlays();
            for ($i=0; $i<count($plays); $i++) {
                $play = [ ];
                $play["quarter"] = $plays[$i]->getQuarter();
                $play["scoring_method"] = $plays[$i]->getScoringMethod();
                
                $team = new Team($plays[$i]->getPossessingTeam());
                $play["team"] = [
                    "name" => $team->getShortName(),
                    "full_name" => $team->getFullName(),
                    "abbr" => $team->getAbbr(),
                    "id" => $team->getId()
                ];
                
                $author = new Player($plays[$i]->getAuthor());
                $play["author"] = [
                    "first_name" => $author->getFirstName(),
                    "last_name" => $author->getLastName(),
                    "full_name" => $author->getFullName(1),
                    "id" => $author->getId()
                ];
                
                $play["type"] = $plays[$i]->getPlayType();
                
                if ($plays[$i]->getPasser()) {
                    $passer = new Player($plays[$i]->getPasser());
                    $play["passer"] = [
                        "first_name" => $passer->getFirstName(),
                        "last_name" => $passer->getLastName(),
                        "full_name" => $passer->getFullName(1),
                        "id" => $passer->getId()
                    ];
                }
                
                do {
                    if (!array_key_exists($i+1, $plays)) {
                        break;
                    }
                    
                    if (!in_array($plays[$i+1]->scoring_method, [ GameTerms::XP, GameTerms::X2P ])) {
                        break;
                    }
                    
                    if (!$plays[$i+1]->isScoringPlay()) {
                        $play["extra"] = [ "result" => "x"];
                        break;
                    }
                    
                    if ($plays[$i+1]->scoring_method == GameTerms::XP) {
                        $play["extra"] = [ "result" => "+1"];
                        if ($plays[$i+1]->getAuthor()) {
                            $extra_author = new Player($plays[$i+1]->getAuthor());
                            $play["extra"]["author"] = [
                                "first_name" => $extra_author->getFirstName(),
                                "last_name" => $extra_author->getLastName(),
                                "full_name" => $extra_author->getFullName(1),
                                "id" => $extra_author->getId()
                            ];
                        }
                        $play["extra"]["type"] = $plays[$i+1]->getPlayType();
                    }
                    
                    if ($plays[$i+1]->scoring_method == GameTerms::X2P) {
                        $play["extra"] = [ "result" => "+2"];
                        if ($plays[$i+1]->getAuthor()) {
                            $extra_author = new Player($plays[$i+1]->getAuthor());
                            $play["extra"]["author"] = [
                                "first_name" => $extra_author->getFirstName(),
                                "last_name" => $extra_author->getLastName(),
                                "full_name" => $extra_author->getFullName(1),
                                "id" => $extra_author->getId()
                            ];
                        }
                        $play["extra"]["type"] = $plays[$i+1]->getPlayType();
                        if ($plays[$i+1]->getPasser()) {
                            $extra_passer = new Player($plays[$i+1]->getPasser());
                            $play["extra"]["passer"] = [
                                "first_name" => $extra_passer->getFirstName(),
                                "last_name" => $extra_passer->getLastName(),
                                "full_name" => $extra_passer->getFullName(1),
                                "id" => $extra_passer->getId()
                            ];
                        }
                    }
                    
                    $i++;
                } while (0);
                
                $play["home_score"] = $event->getHomeScore();
                $play["away_score"] = $event->getAwayScore();
                $drives[] = $play;
            }
        }
        
        foreach($this->drives as $drive) {
            $export["drives"][] = $drive->export();
        }
        
        return $drives;
        return $export;
    }

}