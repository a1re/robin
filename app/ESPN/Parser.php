<?php

namespace Robin\ESPN;

use \Exception;
use \Robin\Logger;
use \Robin\Keeper;
use \Robin\FileHandler;
use \Robin\Team;
use \Robin\Player;
use \Robin\Play;
use \Robin\Drive;
use \Robin\GameTerms;
use \Robin\ESPN\Decompose;

class Parser
{
    use Logger;
    
    private $language;
    private $locale;
    private $data_handler;
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
        if (strlen($url) == 0) {
            throw new Exception("URL of the page cannot be empty");
        }
        
        if (strlen($language) == 0) {
            throw new Exception("Language of the page cannot be empty");
        }
        
        require_once FileHandler::getRoot() . "/app/simplehtmldom_1_9/simple_html_dom.php";
        
        // Checking if we have SimpleHTMLDOM loaded
        if (!function_exists("file_get_html")) {
            throw new Exception("Parsing function not defined");
        }
        
        $this->html = file_get_html($url);
        
        if (!$this->html || !in_array(get_class($this->html), [ "simple_html_dom", "simple_html_dom_node"])) {
            throw new Exception("HTML DOM not received");
        }
        $this->language = $language;
        $this->setLocale($language);
        Team::setDefaultLanguage($language);
        Player::setDefaultLanguage($language);
    }
    
    
    /**
     * Sets locale of the info parsed from the page
     *
     * @param   string  $locale        Locale name, e.g. "en_US"
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
        setlocale(LC_TIME, $locale);
    }

    
    /**
     * Set hadler for reading data
     *
     * @param   Keeper  $data_handler   Keeper object for storing data
     *
     * @return  void         
     */
    public function setDataHandler(Keeper $data_handler): void
    {
        $this->data_handler = $data_handler;
    }
    
    /**
     * Parses scheduled time and returns \DataTime object or null if no info was found
     *
     * @param   string      $timezone   Timezone for DateTimeZone for correcting time
     * @return  DateTime                Scheduled time of event according to time zone
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
     * Parses game title if avalible (usually bowl name)
     *
     * @return  string      Game title
     */
    public function getGameTitle(): ?string
    {
        $game_title = $this->html->find(".game-strip .header", 0);
        
        if ($game_title == null) {
            return null;
        }
        
        return $game_title->plaintext;
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
        $rank = $this->html->find("div.competitors div." . $marker . " div.team-info-wrapper span.rank", 0);
        
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
        
        if ($rank != null && is_object($team)) {
            $team->rank = $rank->plaintext;
        }
        
        if ($this->locale != $this->language) {
            $team->setDataHandler($this->data_handler);
            $team->setLocale($this->locale, true);
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
     * @return  array    Array with home and away subarrays with [0] for total
     *                   score, 1,2,3,4 for each quarter and 5 for overtime
     */
    public function getScore(): ?array
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
            return $result;
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
    private function getLeader(string $category, string $team): Player
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
        
        if ($this->locale != $this->language) {
            $player->setDataHandler($this->data_handler);
            $player->setLocale($this->locale, true);
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
    
    /**
     * Parsing scoring summary section and getting array of instances of Drive
     *
     * @return  array       Array of Drives objects
     */
    public function getScoringDrives(): array
    {
        $scoring_summary = $this->html->find("div[data-module=scoringSummary] div.scoring-summary > table tbody",0);
        
        if ($scoring_summary == null) {
//            throw new Exception("No scoring summary block was found");
            return [ ];
        }
        
        $scoring_summary = $scoring_summary->find("tr");
        $current_quarter = GameTerms::Q1;
        $current_home_score = 0;
        $current_away_score = 0;
        $possessing_team = null;
        $defending_team = null;
        
        $events = [ ];
        
        foreach ($scoring_summary as $e) {
            if (!is_object($e)) {
                continue;
            }
            
            // If current row has "highlight" class, it's identifier of the quarter
            if ($e->getAttribute("class") == "highlight" && $q = $e->find("th.quarter", 0)) {
                $current_quarter = $this->getQuarterByName(trim($q->innertext));
                continue; // We skip to next iteration as here will be no score details
            }
            
            $points_scored = 0;
            $new_home_score = $e->find("td.away-score", 0) ? $e->find("td.away-score", 0)->innertext : 0;
            $new_away_score = $e->find("td.home-score", 0) ? $e->find("td.home-score", 0)->innertext : 0;
            
            $this->setPossessionValues($possessing_team, $defending_team, $points_scored, [
                "current_home_score" => $current_home_score,
                "current_away_score" => $current_away_score,
                "new_home_score" => $new_home_score,
                "new_away_score" => $new_away_score,
                "home_team" => $this->getHomeTeam(),
                "away_team" => $this->getAwayTeam()
            ]);
            
            $scoring_description = $e->find("td.game-details div.table-row div.drives div.headline",0);
            $scoring_description = $scoring_description ? $scoring_description->innertext : '';
            
            $drive = new Drive($possessing_team, $defending_team);
            $drive->setQuarter($current_quarter);
            $drive->setScore($new_home_score, $new_away_score);
            $drive->setResult(true);
            Decompose::setPossessingTeam($possessing_team);
            Decompose::setDefendingTeam($defending_team);
            
            if (in_array($points_scored, [6, 7, 8])) {
                if ($extra_point = Decompose::XP($scoring_description)) {
                    $scoring_description = trim(str_replace([$extra_point->getOrigin(),"(",")"], "", $scoring_description));
                }
                $play = Decompose::TD($scoring_description);
            } elseif ($points_scored == 3) {
                $play = Decompose::FG($scoring_description);
            } else {
                // It's definetely not a touchdown or field goal. We need to parse additional description
                $score_type = $e->find("td.game-details div.table-row .score-type",0);
                
                if ($score_type === null) {
                    continue; // no description of two points, quite the iteration
                }
                
                if ($score_type->innertext == "SF") {
                    $play = Decompose::SF($scoring_description);
                } elseif ($score_type->innertext == "D2P") {
                    $play = Decompose::D2P($scoring_description);
                } elseif (in_array($score_type->innertext, ["XP", "X2P", "2PTC"])) {
                    $play = Decompose::XP($scoring_description);                        
                } else {
                    continue;
                }
            }
            
            if (isset($play)) {
                $play->setQuarter($current_quarter);
                $drive->addPlay($play);
            }
            
            if (isset($extra_point)) {
                $extra_point->setQuarter($current_quarter);
                $drive->addPlay($extra_point);
            }
            
            if ($this->locale != $this->language) {
                $drive->setDataHandler($this->data_handler);
                $drive->setLocale($this->locale);
            }
            $events[] = $drive;
            
            $current_away_score = $new_away_score;
            $current_home_score = $new_home_score;
            unset($play, $extra_point);
        }
        
        return $events;
    }

    /**
     * Calculates possessing+defending teams and points scoeed by values array
     * [ (int) "current_home_score", (int) "current_away_score", (int) "new_home_score",
     * (int) "new_away_score", (string) "home_team", (string) "away_team"]. Variables
     * for possessing team, defending team and points scoread are passed as pointers.
     * They receive new values and method returns void.
     *
     * @param   mixed   &$possessing_team      Variable for possessing team
     * @param   mixed   &$defending_team       Variable for defending team
     * @param   int     &$points_scored        Variable for points scored
     * @param   array   $values                Aray of values for calculation
     * @return  void
     */
    private function setPossessionValues(&$possessing_team, &$defending_team, int &$points, array $values): void
    {
        $keys = [ "current_home_score", "current_away_score", "new_home_score", "new_away_score" ];
        
        foreach ($keys as $key) {
            if (isset($values[$key]) && is_numeric($values[$key])) {
                ${$key} = $values[$key];
            } else {
                throw new Exception("Values array should contain numeric '" .$key . "' value");
            }            
        }
        
        if (is_a($values["home_team"], "\Robin\Team")) {
            $home_team = $values["home_team"];
        } else {
            throw new Exception("Home team value must be a valid Team object");            
        }
        
        if (is_a($values["away_team"], "\Robin\Team")) {
            $away_team = $values["away_team"];
        } else {
            throw new Exception("Away team value must be a valid Team object");            
        }
        
        $home_delta = $new_home_score-$current_home_score;
        $away_delta = $new_away_score-$current_away_score;
        
        if ($away_delta > $home_delta) {
            $possessing_team = $away_team;
            $defending_team = $home_team;
            $points = $away_delta;
        } else {
            $possessing_team = $home_team;
            $defending_team = $away_team;
            $points = $home_delta;
        }
    }
    
    /**
     * Parses quarter header and returns one of Event constants, that
     * could be used to identify the quarter;
     *
     * @return  string  quarter identifier equal to GameTerms::Q1/Q2/Q3/Q4/OT
     */
    private function getQuarterByName(string $name = null): string
    {
        // If name is empty, we keep it as first quarter
        if (mb_strlen($name) == 0) {
            return GameTerms::Q1;
        }
        
        $quarter_header = explode(" ", $name);
        
        if (count($quarter_header) == 2 && mb_strtolower($quarter_header[1]) == "quarter") {
            switch ($quarter_header[0]) {
                case 'first':   return GameTerms::Q1;
                case 'second':  return GameTerms::Q2;
                case 'third':   return GameTerms::Q3;
                case 'fourth':  return GameTerms::Q4;
            }
        }
        
        // if header is not empty and doesn't start with first, second, third or fourth, it's overtime
        return GameTerms::OT;
    }

    /**
     * Parses standing page and returns teams and standigs values.
     * 
     * @return  array   list of tables with Team objects and standigs' values
     */
    public function getTablesList(): array
    {
        $tables = [];
        foreach ($this->html->find(".standings__table") as $table) {
            $table_name = $table->find(".Table__Title", 0);
            if (!$table_name) {
                continue;
            }

            $table_teams = $table->find("table.Table", 0);
            if (!$table_teams) {
                continue;
            }

            $divisions = [];
            $division_index = 0;

            foreach ($table_teams->find("tbody tr") as $table_row_index=>$table_row) {

                if ($table_row->hasClass("subgroup-headers")) {
                    $divison_name = $table_row->find("span", 0);

                    if (!$divison_name) {
                        continue;
                    }

                    if (count($divisions) > 0) {
                        $division_index++;
                    }

                     $divisions[$division_index] =  [
                        "name" => $divison_name->plaintext,
                        "teams" => []
                    ];

                    continue;
                }

                $team_block = $table_row->find("div.team-link", 0);
                if (!$team_block) {
                    continue;
                }

                if (count($divisions) === 0) {
                    $divisions[$division_index] =  ["teams" => []];
                }

                $team_name = $team_block->find(".hide-mobile a", 0);
                if ($team_name) {
                    $team_name = $team_name->plaintext;
                } else {
                    continue;
                }

                $team_abbr = $team_block->find(".show-mobile abbr", 0);
                if ($team_abbr) {
                    $team_abbr = $team_abbr->plaintext;
                } else {
                    $team_abbr = "";
                }

                $team = new Team($team_name, "", $team_abbr);

                $team_img = $team_block->find(".TeamLink__Logo a", 0);

                if ($team_img) {
                    $team_img_pattern = "#/(nfl|college-football)/team/_/(id|name)/([a-z0-9]+)/.+#i";
                    $team_img_data = [];
                    $team_img_matches = preg_match_all($team_img_pattern, $team_img->getAttribute("href"), $team_img_data);

                    if ($team_img_matches) {
                        if (isset($team_img_data[1][0]) && $team_img_data[1][0] === "college-football") {
                            $team_img_data[1][0] = "ncaa";
                        }
                        $team_img = 'https://a.espncdn.com/combiner/i?img=/i/teamlogos/' . $team_img_data[1][0] . '/500/' . $team_img_data[3][0] . '.png&h=30&w=30';
                    } else {
                        $team_img = NULL;
                    }
                } else {
                    $team_img = NULL;
                }

                $team_rank = $team_block->find(".pr2", 0);

                if ($team_rank) {
                    $team->rank = $team_rank->plaintext;
                }
        
                if ($this->locale != $this->language) {
                    $team->setDataHandler($this->data_handler);
                    $team->setLocale($this->locale, true);
                }

                array_push($divisions[$division_index]["teams"], [
                    "team" => $team,
                    "logo" => $team_img,
                    "row_index" => $table_row_index
                ]);
            }

            $table_values = $table->find("table.Table", 1);
            if (!$table_values) {
                continue;
            }

            $value_rows = $table_values->find("tbody tr");

            foreach ($divisions as $division_index => $division) {
                foreach ($division["teams"] as $team_index => $team) {
                    if (!array_key_exists($team["row_index"], $value_rows)) {
                        continue;
                    }

                    $value_row = $value_rows[$team["row_index"]];

                    $values = $value_row->find("span");
                    if (!$values) {
                        continue;
                    }

                    $divisions[$division_index]["teams"][$team_index]["values"] = [
                        "conference" => $values[0]->plaintext,
                        "overall" => $values[3]->plaintext,
                        "home" => $values[6]->plaintext,
                        "away" => $values[7]->plaintext,
                        "streak" => $values[8]->plaintext
                    ];

                    $divisions[$division_index]["teams"][$team_index]["team"]->streak = $values[8]->plaintext;
                 }
            }

            array_push($tables, [
                "name" => $table_name->plaintext . '!',
                "divisions" => $divisions
            ]);
        }

        return $tables;
    }
}