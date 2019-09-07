<?php
    
namespace Robin\ESPN;

use \Exception;
use \ReflectionClass;
use \ReflectionMethod;
use \Robin\Logger;
use \Robin\Interfaces\ParsingEngine;
use \Robin\Exceptions\ParsingException;
use \Robin\ESPN\Team;
use \Robin\ESPN\Player;
use \Robin\ESPN\ScoringEvent;

class Gamecast implements ParsingEngine
{
    use Logger;
    
    protected $html;
    private $methods;
    
    private $home_team;
    private $away_team;
    public $players = [ ];
    
    public function __construct($html)
    {
        if (!$html || !in_array(get_class($html), [ "simple_html_dom", "simple_html_dom_node"])) {
            throw new ParsingException("HTML DOM not received");
        }
        
        $this->html = $html;
    }
    
    public function getMethods(): array
    {
        $class = new ReflectionClass($this);
        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method_object) {
            if (!in_array($method_object->name, [ "log", "__construct" ])) {
               $this->methods[] = $method_object->name; 
            }
        }
        
        return $this->methods;
    }
    
    /**
     * Getting team object from the page
     *
     * @param   string   $marker    Class name in page source code (usually "home" or "away")
     * @return  Team                Instance of Team class
     */
    private function getTeam(string $marker): Team
    {
        // Taking team names from HTML
        $first_name = $this->html->find("div.competitors div." . $marker . " a.team-name .long-name", 0);
        $last_name = $this->html->find("div.competitors div." . $marker . " a.team-name .short-name", 0);
        $abbr_name = $this->html->find("div.competitors div." . $marker . " a.team-name .abbrev", 0);
        
        $full_name = "";
        $short_name = "";
        $abbr = "";
        
        if ($first_name != null) {
            // If block with both city and name was found
            if ($last_name != null) {
                $full_name = $first_name->plaintext . ' ' . $last_name->plaintext;
            }
            
            $short_name = $first_name->plaintext;
        }
        
        if ($abbr_name != null) {
            $abbr = $abbr_name->plaintext;
        }
        
        return new Team($full_name, $short_name, $abbr);
    }
    
    /**
     * Getting Player entity with name and stats of the "Game Leaders" section
     *
     * @param   string  $category   DOM dataset key in page source code for stats category
     * @param   string  $team       DOM dataset key in page source code for team type (usually "home" or "away")
     * @return  Player              Instance of Player class
     */
    private function getLeader(string $category, string $team): Player
    {
        //Parsing player name with DOM request with $category and $team markers
        $player_name_query  = "div[data-module=teamLeaders] div[data-stat-key=";
        $player_name_query .= $category . "Yards] ." . $team . "-leader .player-name";
        $player_name_tag = $this->html->find($player_name_query, 0);
        $player_name = $player_name_tag ? $player_name_tag->getAttribute("title") : null;
        
        $player = null;
        
        // Checking if player is among existing player objects
        if (!array_key_exists($team, $this->players)) {
            $this->players[$team] = [ ];
        }
        
        if (array_key_exists($player_name, $this->players[$team])) {
            $player_object = $this->players[$team][$player_name];
            if (is_object($player_object) && (new \ReflectionClass($player_object))->getShortName() == "Player") {
                $player = $player_object;
            } 
        }
        
        if (!$player) {
            // If $player_name is null or zero length, Player constructor will throw exception
            $player = new Player($player_name);
            $this->players[$team][$player_name] = $player;
        }
        
        //Parsing player stats with DOM request with $category and $team markers
        $player_stat_query  = "div[data-module=teamLeaders] div[data-stat-key=";
        $player_stat_query .= $category . "Yards] ." . $team . "-leader .player-stats";
        $player_stat_tag = $this->html->find($player_stat_query, 0);
        $player_stat = $player_stat_tag ? $player_stat_tag->plaintext : null;
        
        // If no stats were found, we just return player instance without them
        if ($player_stat) {
            $player_stat = str_replace("&nbsp;", " ", $player_stat); // remove all unnecessary spaces
            $player_stat = str_replace("&nbsp", " ", $player_stat); // for some reason, &nbsp without ";" sometimes appear
            $player_stat = preg_replace("~[^\w -]+~", "", $player_stat); // All other non-word characters
            
            // Passing attempts and completions
            preg_match('/([0-9]{1,2})-([0-9]{1,2}),/', $player_stat, $matches);
            if (count($matches) > 2) {
                $method = "set" . ucfirst($category) . "Attempts";
                $player->{$method}($matches[2]);
                
                $method = "set" . ucfirst($category) . "Completions";
                $player->{$method}($matches[1]);
            }
    
            // Parsing indexes form player stats with the same patterns. 
            $patterns = [ "Yards" => "yds?", "TD" => "td", "Interceptions" => "int",
                          "Carries" => "car", "Receptions" => "rec" ];
              
            foreach ($patterns as $name => $pattern) {
                preg_match('/([0-9]{1,3}) ' . $pattern . '?/i', $player_stat, $matches);
                if (count($matches) > 1) {
                    $method = "set" . ucfirst($category) . $name;
                    $player->{$method}($matches[1]);
                }
            }
        }
        return $player;
    }
    
    /**
     * Parsing scoring summary section and getting array of instances of ScoringEvent
     *
     * @return  array   Ordered list of scoring events
     */
    public function getScoringEvents(): array
    {
        $scoring_summary = $this->html->find("div[data-module=scoringSummary] div.scoring-summary > table tbody",0);
        
        if (!$scoring_summary) {
            $this->log("No scoring summary block was found");
            return [ ];
        }
        
        $scoring_summary = $scoring_summary->find("tr");
        $current_quarter = ScoringEvent::Q1;
        $current_home_score = 0;
        $current_away_score = 0;
        
        foreach ($scoring_summary as $e) {
            if (!is_object($e)) {
                $this->log("Row is not an object");
                continue;
            }
            
            // If current row has "highlight" class, it's identifier of the quarter
            if ($e->getAttribute("class") == "highlight") { 
                $quarter_name = $e->find("th.quarter", 0);
                if ($quarter_name) {
                    $current_quarter = $this->getQuarterByName(trim($quarter_name->innertext));                
                }
                continue;
            }
            
            // In scoring summary on ESPN home and away columns are swipped
            $new_home_score = $e->find("td.away-score", 0);
            if ($new_home_score) {
                $new_home_score = (int) $e->find("td.away-score", 0)->innertext;
            } else {
                $new_home_score = 0;
            }

            $new_away_score = $e->find("td.home-score", 0);
            if ($new_away_score) {
                $new_away_score = (int) $e->find("td.home-score", 0)->innertext;
            } else {
                $new_away_score = 0;
            }
            
            // If we got nulls, it's better to get out of the iteration
            if ($new_home_score + $new_away_score == 0) {
                $this->log("Score values are not parsed");
                continue;
            }
            
            // Delta is a key identifier of who scored and how many points. If it's
            // positive, then home team is the scorer, if negative — away. To work
            // with points, we take abs($delta);
            $delta = $new_home_score-$current_home_score-$new_away_score+$current_away_score;
            
            // Based on delta, we get Team object
            $scoring_team = ($delta < 0) ? $this->getAwayTeam() : $this->getHomeTeam();
            
            $scoring_description = $e->find("td.game-details div.table-row div.drives div.headline",0);
            $scoring_description = $scoring_description ? $scoring_description->innertext : '';
            
            $matches = [ ];
            $regexp = [ "name" => "([a-zA-Z-.\' ]+)" ];
            if (abs($delta) == 7) {
                
            } else if (abs($delta) == 6) {
                
            } else if (abs($delta) == 8) {
                
            } else if (abs($delta) == 3) {
                
            } else if (abs($delta) == 2) {
                
            }
            
            var_dump($current_quarter ." ". $scoring_team->abbr . " " . $scoring_description . " " . $new_home_score . ":" . $new_away_score . " (" . $delta .")");
            
            $current_home_score = $new_home_score;
            $current_away_score = $new_away_score;
        } 
        
        return [ ];
    }
    
    private function decomposeTD(string $scoring_description): ?object
    {
        $methods = [ self::RUN => "",
                     self::RECEPTION => "",
                     self::INTERCEPTION_RETURN => "",
                     self::KICKOFF_RETURN => "",
                     self::PUNT_RETURN => "",
                     self::FUMBLE_RETURN => "",
                     self::FUMBLE_RECOVERY => ""];
        $name_regexp = "";
        
    }
    
    /**
     * Parses quarter header and returns one of ScoringEvent constants, that
     * could be used to identify the quarter;
     *
     * @return  string  quarter identifier equal to ScoringEvent::Q1/Q2/Q3/Q4/OT
     */
    private function getQuarterByName(string $name = null): string
    {
        if (mb_strlen($name) == 0) {
            return ScoringEvent::Q1;
        }
        
        $quarter_header = explode(" ", $name);
        
        if (count($quarter_header) == 2 && mb_strtolower($quarter_header[1]) == "quarter") {
            switch ($quarter_header[0]) {
                case 'first':   return ScoringEvent::Q1;
                case 'second':  return ScoringEvent::Q2;
                case 'third':   return ScoringEvent::Q3;
                case 'fourth':  return ScoringEvent::Q4;
            }
        }
        
        return ScoringEvent::OT;
    }
    
    /**
     * Public shortcut for getTeam with home marker
     *
     * @return  Team    Instance of Team class
     */
    public function getHomeTeam(): Team
    {
        if (!(is_object($this->home_team) && (new \ReflectionClass($this->home_team))->getShortName() == "Team")) {
            $this->home_team = $this->getTeam("home");
        }
        
        return $this->home_team;
    }
    
    /**
     * Public shortcut for getTeam with away marker
     *
     * @return  Team    Instance of Team class
     */
    public function getAwayTeam(): Team
    {
        if (!(is_object($this->away_team) && (new \ReflectionClass($this->away_team))->getShortName() == "Team")) {
            $this->away_team = $this->getTeam("away");
        }
        
        return $this->away_team;
    }
    
    /**
     * Public shortcuts for getLeader(); with preset names
     *
     * @return  Player  Instance of Player class
     */
    public function getHomePassingLeader(): Player   { return $this->getLeader("passing", "home");   }
    public function getHomeRushingLeader(): Player   { return $this->getLeader("rushing", "home");   }
    public function getHomeReceivingLeader(): Player { return $this->getLeader("receiving", "home"); }
    public function getAwayPassingLeader(): Player   { return $this->getLeader("passing", "away");   }
    public function getAwayRushingLeader(): Player   { return $this->getLeader("rushing", "away");   }
    public function getAwayReceivingLeader(): Player { return $this->getLeader("receiving", "away"); }
    
}