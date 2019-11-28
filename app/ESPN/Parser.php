<?php

namespace Robin\ESPN;

use \Exception;
use \Robin\Logger;
use \Robin\FileHandler;
use \Robin\Team;
use \Robin\Player;
use \Robin\ESPN\Decompose;

class Parser
{
    use Logger;
    
    private $source_language;
    private $language;
    
    private $home_team;
    private $away_team;
    
    /**
     * Class constructor
     *
     * @param   string  $url            URL of the page to be parsed
     * @param   string  $language       Original language of the page
     */
    public function __construct(string $url, string $language)
    {
        require_once FileHandler::getRoot() . "/app/simplehtmldom_1_9/simple_html_dom.php";
        
        // Checking if we have SimpleHTMLDOM loaded
        if (!function_exists("file_get_html")) {
            throw new Exception("Parsing function not defined");
        }
        
        $this->html = file_get_html($url);
        
        if (!$this->html || !in_array(get_class($this->html), [ "simple_html_dom", "simple_html_dom_node"])) {
            throw new ParsingException("HTML DOM not received");
        }
        $this->source_language = $language;
        $this->setLanguage($language);
        
        Team::setDefaultLanguage($language);
    }
    
    
    /**
     * Sets active language of the info to be parsed from the page
     *
     * @param   string  $language        Language name
     */
    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }
    
    /**
     * Parses scheduled time and returns \DataTime object or null if no info was found
     *
     * @return  DateTime  scheduled time of event
     */
    public function getScheduleTime(string $timezone): ?\DateTime
    {
        $game_date_time = $this->html->find(".game-date-time span[data-behavior=date_time]", 0);
        
        if ($game_date_time == null) {
            return null;
        }
        
        $datetime = $game_date_time->getAttribute("data-date");
        
        if (mb_strlen($datetime) > 0 && $d = new \DateTime($datetime)) {
            $d->setTimezone(new \DateTimeZone($timezone));
            return $d;
        }
        return null;
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
        $logo_tag = $this->html->find("div.competitors div." . $marker . " div.team-info-logo img.team-logo", 0);
        
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
        
        $team = new Team($full_name, $short_name, $abbr);
        
        if ($logo_tag != null && is_object($team)) {
            $img_url = preg_replace('/(h|w)\=(\d{2,3})/', '$1=150', $logo_tag->getAttribute("src"));
            if (filter_var($img_url, FILTER_VALIDATE_URL)) {
                $team->img = $img_url;
            }
        }
        
        return $team;
    }
    
    /**
     * Public shortcut for getTeam with home marker
     *
     * @return  Team    Instance of Team class
     */
    public function getHomeTeam(): Team
    {
        if ($this->home_team == null){
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
        if ($this->away_team == null){
            $this->away_team = $this->getTeam("away");
        }
        
        return $this->away_team;
    }
    
    /**
     * Parsing score by quarters
     *
     * @return  object   Object with home and away values, both of them are arrays
     *                   with [0] for total score, 1,2,3,4 for each quarter and 5
     *                   for overtime
     */
    public function getScore(): ?\stdClass
    {
        $score_row = $this->html->find("table#linescore tbody tr");
        
        $result = [ ];
        $keys = [ 0 => "away", 1 => "home" ];
        
        if ($score_row !== null) {
            foreach ($keys as $key=>$team) {
                if (array_key_exists($key, $score_row) && is_object($score_row[$key])) {
                    $score_cells = $score_row[$key]->find("td");
                    foreach ($score_cells as $n => $score) {
                        $cell_class = $score->getAttribute("class");
                        if ($cell_class == "team-name") {
                            //do nothing, skip
                        } else if ($cell_class == "final-score") {
                            $result[$team][0] = $score->innertext();
                        } else {
                            $result[$team][$n] = $score->innertext();
                        }
                    }
                }
            }
        }
        
        if (count($result) > 0) {
            return (object) $result;
        } else {
            return null;
        }
    }
    
    /**
     * Getting Player entity with name and stats of the "Game Leaders" section
     *
     * @param   string  $category   DOM dataset key in page source code for stats category
     * @param   string  $team       DOM dataset key in page source code for team type (usually "home" or "away")
     * @return  Player              Instance of Player class
     */
    private function getLeader(string $category, string $team)
    {
        if ($team == "home") {
            $team_id = $this->getHomeTeam();
        } else if ($team == "away") {
            $team_id = $this->getAwayTeam();
        }
        
        if (empty($team_id)) {
            throw new Exception("Unknown team '" . $team ."'");
        }
        
        //Parsing player name with DOM request with $category and $team markers
        $player_name_query  = "div[data-module=teamLeaders] div[data-stat-key=";
        $player_name_query .= $category . "Yards] ." . $team . "-leader .player-name";
        $player_name_tag = $this->html->find($player_name_query, 0);
        $player_name = $player_name_tag ? $player_name_tag->getAttribute("title") : null;
        
        if (empty($player_name)) {
            throw new Exception("No player name parsed from page");
        }
        
        $player = new Player($player_name);
        $player->setId($team_id->full_name . '/' . $player_name);
        
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
                $player->setStats(ucfirst($category) . "Attempts", $matches[2]);
                $player->setStats(ucfirst($category) . "Completions", $matches[1]);
            }
            
            // Parsing indexes form player stats with the same patterns. 
            $patterns = [ "Yards" => "yds?", "TD" => "td", "Int" => "int",
                          "Carries" => "car", "Receptions" => "rec" ];
              
            foreach ($patterns as $name => $pattern) {
                preg_match('/([0-9]{1,3}) ' . $pattern . '?/i', $player_stat, $matches);
                if (count($matches) > 1) {
                    $player->setStats(ucfirst($category) . $name, $matches[1]);
                }
            }
        }
        return $player;
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