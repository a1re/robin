<?php

namespace Robin\ESPN;

use \Exception;
use \Robin\Logger;
use \Robin\Player;
use \Robin\Team;
use \Robin\Drive;
use \Robin\GameTerms;
use \Robin\Inflector;
use \Robin\Keeper;
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
    private $keeper;
    
    /**
     * Class constructor
     *
     * @param   string  $url            URL of the page to get info
     * @param   string  $language       Source language of the page
     * @param   string  $locale         (optional) Locale of the parsed data
     */
    public function __construct($url, string $language, string $locale = "")
    {
        $this->keeper = new Keeper(new FileHandler("data"));
        
        if (is_array($url)) {
            $this->import($url);
            return;
        }
        
        $parser = new Parser($url, $language);
        $parser->setDatahandler($this->keeper);
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
            $this->$team_name->setDataHandler($this->keeper);
            foreach (self::LEADERS_LIST as $leader) {
                $leader_name = $team . "_" . $leader;
                if (!array_key_exists($leader_name, $values) && !is_array($values[$leader_name])) {
                    throw new Exception("Import array lacks '" . $leader_name . "' value");
                }
                
                $this->$leader_name = new Player($values[$leader_name]);
                $leader_name_id = $this->$team_name->getFullName() . "/" . $this->$leader_name->getFullName();
                $this->$leader_name->setId($leader_name_id);
                $this->$leader_name->setDataHandler($this->keeper);
            }
        }
        
        if (!array_key_exists("drives", $values) || !is_array($values["drives"])) {
            throw new Exception("Import array lacks 'drives' value");
        }
        
        foreach ($values["drives"] as $drive_export){
            if (is_array($drive_export) && $drive = new Drive($drive_export)) {
                $drive->setDataHandler($this->keeper);
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
            "logo_height" => 150
        ];
        
        $away_team = [
            "name" => $this->away_team->getShortName(),
            "full_name" => $this->away_team->getFullName(),
            "abbr" => $this->away_team->getAbbr(),
            "logo" => $this->away_team->getImg(),
            "logo_width" => 150,
            "logo_height" => 150
        ];
        
        return [
            "home_team" => $home_team,
            "home_team_score" => $this->score["home"][0],
            "away_team" => $away_team,
            "away_team_score" => $this->score["away"][0]
        ];
    }
    
    public function quarters(): array
    {
        $home_score = [
            "team" => [
                "name" => $this->home_team->getShortName(),
                "full_name" => $this->home_team->getFullName(),
                "abbr" => $this->home_team->getAbbr(),
                "logo" => $this->home_team->getImg(),
                "logo_width" => 150,
                "logo_height" => 150
            ],
            "q1" => $this->score["home"][1],
            "q2" => $this->score["home"][2],
            "q3" => $this->score["home"][3],
            "q4" => $this->score["home"][4],
        ];
        
        if (array_key_exists(5, $this->score["home"])) {
            $home_score["ot"] = $this->score["home"][5];
        } else {
            $home_score["ot"] = null;
        }
        
        $home_score["total"] = $this->score["home"][0];
        
        $away_score = [
            "team" => [
                "name" => $this->away_team->getShortName(),
                "full_name" => $this->away_team->getFullName(),
                "abbr" => $this->away_team->getAbbr(),
                "logo" => $this->away_team->getImg(),
                "logo_width" => 150,
                "logo_height" => 150
            ],
            "q1" => $this->score["away"][1],
            "q2" => $this->score["away"][2],
            "q3" => $this->score["away"][3],
            "q4" => $this->score["away"][4],
        ];
        
        if (array_key_exists(5, $this->score["away"])) {
            $away_score["ot"] = $this->score["away"][5];
        } else {
            $away_score["ot"] = null;
        }
        
        $away_score["total"] = $this->score["away"][0];
        
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
                
                $team = $plays[$i]->getPossessingTeam();
                $play["team"] = [
                    "name" => $team->getShortName(),
                    "full_name" => $team->getFullName(),
                    "abbr" => $team->getAbbr(),
                    "id" => $team->getId()
                ];
                
                $author = $plays[$i]->getAuthor();
                $play["author"] = [
                    "first_name" => $author->getFirstName(),
                    "last_name" => $author->getLastName(),
                    "full_name" => $author->getFullName(["include_position_and_number" => true]),
                    "id" => $author->getId()
                ];
                
                $play["type"] = $plays[$i]->getPlayType();
                
                if ($plays[$i]->getPasser()) {
                    $passer = $plays[$i]->getPasser();
                    $play["passer"] = [
                        "first_name" => $passer->getFirstName(),
                        "last_name" => $passer->getLastName(),
                        "full_name" => $passer->getFullName(["include_position_and_number" => true, "genitive" => true]),
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
                            $extra_author = $plays[$i+1]->getAuthor();
                            $play["extra"]["author"] = [
                                "first_name" => $extra_author->getFirstName(),
                                "last_name" => $extra_author->getLastName(),
                                "full_name" => $extra_author->getFullName(["include_position_and_number" => true]),
                                "id" => $extra_author->getId()
                            ];
                        }
                        $play["extra"]["type"] = $plays[$i+1]->getPlayType();
                    }
                    
                    if ($plays[$i+1]->scoring_method == GameTerms::X2P) {
                        $play["extra"] = [ "result" => "+2"];
                        if ($plays[$i+1]->getAuthor()) {
                            $extra_author = $plays[$i+1]->getAuthor();
                            $play["extra"]["author"] = [
                                "first_name" => $extra_author->getFirstName(),
                                "last_name" => $extra_author->getLastName(),
                                "full_name" => $extra_author->getFullName(["include_position_and_number" => true]),
                                "id" => $extra_author->getId()
                            ];
                        }
                        $play["extra"]["type"] = $plays[$i+1]->getPlayType();
                        if ($plays[$i+1]->getPasser()) {
                            $extra_passer = $plays[$i+1]->getPasser();
                            $play["extra"]["passer"] = [
                                "first_name" => $extra_passer->getFirstName(),
                                "last_name" => $extra_passer->getLastName(),
                                "full_name" => $extra_passer->getFullName(["include_position_and_number" => true, "genitive" => true]),
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
        
        return $drives;
    }

}