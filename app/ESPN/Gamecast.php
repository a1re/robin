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
        
        // If $player_name is null or zero length, Player constructor will throw exception
        $player = new Player($player_name);
        
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
     * Public shortcut for getTeam with home and away markers
     *
     * @return  Team                Instance of Team class
     */
    public function getHomeTeam(): Team { return $this->getTeam("home"); }
    public function getAwayTeam(): Team { return $this->getTeam("away"); }
    
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