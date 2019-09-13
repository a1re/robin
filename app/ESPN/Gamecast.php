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
use \Robin\ESPN\Event;

class Gamecast implements ParsingEngine
{
    use Logger;
    
    protected $html;
    private $methods;
    
    private $home_team;
    private $away_team;
    public $players = [ ];
    
    protected $name_pattern_2w = "[a-zA-Z-.\']+\s[a-zA-Z-.\']+";
    protected $name_pattern_3w = "[a-zA-Z-.\']+\s[a-zA-Z-.\']+[a-zA-Z-.\'\s]*";
    
    public function __construct($html)
    {
        if (!$html || !in_array(get_class($html), [ "simple_html_dom", "simple_html_dom_node"])) {
            throw new ParsingException("HTML DOM not received");
        }
        
        $this->html = $html;
        $this->getHomeTeam();
        $this->getAwayTeam();
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
        
        //Adds player to array of Players or returns instance of existing Player
        $player = $this->addPlayer($player_name, $team);
                
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
     * Parsing scoring summary section and getting array of instances of Event
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
        $current_quarter = Event::Q1;
        $current_home_score = 0;
        $current_away_score = 0;
        
        $events = [ ];
        
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
                continue; // We skip to next iteration as here will be no score details
            }
            
            // In scoring summary on ESPN home and away columns are swapped
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
            //
            // In case both teams scores increased in one event, we take bigger
            // and nullify delta of other.
            if ($new_home_score-$current_home_score > $new_away_score-$current_away_score) {
                $delta = $new_home_score-$current_home_score;
                $new_away_score = $current_away_score;
                $scoring_team = $this->getHomeTeam();
            } else if ($new_home_score-$current_home_score < $new_away_score-$current_away_score) {
                $delta = (-1)*($new_away_score-$current_away_score);
                $new_home_score = $current_home_score;
                $scoring_team = $this->getAwayTeam();
            }
            
            $scoring_description = $e->find("td.game-details div.table-row div.drives div.headline",0);
            $scoring_description = $scoring_description ? $scoring_description->innertext : '';
            
//            echo $current_quarter ." ". $scoring_team->abbr . " " . $scoring_description . " " . $new_home_score . ":" . $new_away_score . " (" . $delta .")" . PHP_EOL;
            
            // Decomposing extea point first to cut off conversion description from touchdown
            if(in_array(abs($delta), [6,7,8])) {
                if ($conversion_event = $this->decomposeXP($scoring_description, $scoring_team)) {
                    $scoring_description = trim(str_replace([$conversion_event->origin,"(",")"], "", $scoring_description));
                    
                    $conversion_event->setQuarter($current_quarter);
                    $conversion_event->setScore($new_home_score, $new_away_score);
                }
            }
            
            if (abs($delta) == 7) {
                $adjusted_home_score = ($delta < 0) ? $new_home_score : $new_home_score-1;
                $adjusted_away_score = ($delta > 0) ? $new_away_score : $new_away_score-1;
                
                if ($scoring_event = $this->decomposeTD($scoring_description, $scoring_team)) {
                    $scoring_event->setScore($adjusted_home_score, $adjusted_away_score);
                }
            } else if (abs($delta) == 6) {
                if ($scoring_event = $this->decomposeTD($scoring_description, $scoring_team)) {
                    $scoring_event->setScore($new_home_score, $new_away_score);
                }
            } else if (abs($delta) == 8) {
                $adjusted_home_score = ($delta < 0) ? $new_home_score : $new_home_score-2;
                $adjusted_away_score = ($delta > 0) ? $new_away_score : $new_away_score-2;
                
                if ($scoring_event = $this->decomposeTD($scoring_description, $scoring_team)) {
                    $scoring_event->setScore($adjusted_home_score, $adjusted_away_score);
                }
            } else if (abs($delta) == 3) {
                if ($scoring_event = $this->decomposeFG($scoring_description, $scoring_team)) {
                    $scoring_event->setScore($new_home_score, $new_away_score);
                }
            } else {
                // It's definetely not a touchdown or field goal. We need to parse additional description
                $score_type = $e->find("td.game-details div.table-row .score-type",0);
                
                if ($score_type === null) {
                    continue; // no description of two points, quite the iteration
                }
                
                if ($score_type->innertext == "SF") {
                    if ($scoring_event = $this->decomposeSF($scoring_description, $scoring_team)) {
                        $scoring_event->setScore($new_home_score, $new_away_score);
                    }
                } else if ($score_type->innertext == "D2P") {
                    if ($scoring_event = $this->decomposeD2P($scoring_description, $scoring_team)) {
                        $scoring_event->setScore($new_home_score, $new_away_score);
                    }
                } else if (in_array($score_type->innertext, ["XP", "X2P", "2PTC"])) {
                    var_dump($scoring_description);
                    var_dump($scoring_team->abbr);
                    if ($scoring_event = $this->decomposeXP($scoring_description, $scoring_team)) {
                        $scoring_event->setScore($new_home_score, $new_away_score);
                    }
                } else {
                    continue; // no idea of what is this, quite the iteration
                }
            }
            
            $current_home_score = $new_home_score;
            $current_away_score = $new_away_score;
            
            if (isset($scoring_event)) {
                $scoring_event->setQuarter($current_quarter);
                $events[] = $scoring_event;
                unset($scoring_event);
            }
            if (isset($conversion_event)) {
                $events[] = $conversion_event;
                unset($conversion_event);
            }
        }
        
        return $events;
    }

    /**
     * Converts textual touchdown description to instance of ESPN\Event. Takes
     * scoring event description, preferably with cut off conversion description, e.g.
     * "Samson Ebukam 25 Yd Interception Return". If string will contain conversion description
     * not wrapped by brackets, it may cause inaccuracies in parsing and decomposing
     * into ESPN\Event object. It'sbetter to use ESPN\Gamecast::decomposeXP first
     * and cut off the extra-point(s) part by origin decomposed by decomposeXP.
     *
     * @param   string  $scoring_description   Full textual description of TD, incl. XP
     * @param   Team    $team                  Instance of Team that scored points
     * @return  Event  Instance of ESPN\Event class with decomposed info.
     */
    
    private function decomposeTD(string $scoring_description, Team $team): ?Event
    {
        $matches = [ ];
        
        // Run play touchdown
        $pattern = "(" . $this->name_pattern_3w . ")\s\d{1,3}\sya?r?ds?\srun";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $e = new Event(Event::RUN, Event::TD);
            $e->setTeam($team);
            $e->setOrigin($matches[0]);
            
            if(array_key_exists(1, $matches) && mb_strlen($matches[1]) > 0) {
                $e->setAuthor($this->addPlayer($matches[1], $team));
            }
            
            return $e;
        }
        
        // Pass play touchdown
        $pattern = "(" . $this->name_pattern_3w . ")\s\d{1,3}\sya?r?ds?\spass\sfrom\s(" . $this->name_pattern_3w . ")";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $e = new Event(Event::RECEPTION, Event::TD);
            $e->setTeam($team);
            $e->setOrigin($matches[0]);
            
            if(array_key_exists(2, $matches) && mb_strlen($matches[2]) > 0) {
                $e->setPasser($this->addPlayer($matches[2], $team));
            }
            
            if(array_key_exists(1, $matches) && mb_strlen($matches[1]) > 0) {
                $e->setAuthor($this->addPlayer($matches[1], $team));
            }
            
            return $e;
        }
        
        // Interception return touchdown
        $pattern = "(" . $this->name_pattern_3w . ")\s\d{1,3}\sya?r?ds?\sinterception\sreturn";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $e = new Event(Event::INTERCEPTION_RETURN, Event::TD);
            $e->setTeam($team);
            $e->setOrigin($matches[0]);
            
            if(array_key_exists(1, $matches) && mb_strlen($matches[1]) > 0) {
                $e->setAuthor($this->addPlayer($matches[1], $team));
            }
            
            return $e;
        }
        
        // Fumble return touchdown
        $pattern = "(" . $this->name_pattern_3w . ")\s\d{1,3}\sya?r?ds?\sfumble\sreturn";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $e = new Event(Event::FUMBLE_RETURN, Event::TD);
            $e->setTeam($team);
            $e->setOrigin($matches[0]);
            
            if(array_key_exists(1, $matches) && mb_strlen($matches[1]) > 0) {
                $e->setAuthor($this->addPlayer($matches[1], $team));
            }
            
            return $e;
        }
        
        // Fumble recovery touchdown
        $pattern = "(" . $this->name_pattern_3w . ")\s\d{1,3}\sya?r?ds?\sfumble\srecovery";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $e = new Event(Event::FUMBLE_RECOVERY, Event::TD);
            $e->setTeam($team);
            $e->setOrigin($matches[0]);
            
            if(array_key_exists(1, $matches) && mb_strlen($matches[1]) > 0) {
                $e->setAuthor($this->addPlayer($matches[1], $team));
            }
            
            return $e;
        }
        
        // Punt return touchdown
        $pattern = "(" . $this->name_pattern_3w . ")\s\d{1,3}\sya?r?ds?\spunt\sreturn";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $e = new Event(Event::PUNT_RETURN, Event::TD);
            $e->setTeam($team);
            $e->setOrigin($matches[0]);
            
            if(array_key_exists(1, $matches) && mb_strlen($matches[1]) > 0) {
                $e->setAuthor($this->addPlayer($matches[1], $team));
            }
            
            return $e;
        }
        
        // Kickoff return touchdown
        $pattern = "(" . $this->name_pattern_3w . ")\s\d{1,3}\sya?r?ds?\skickoff\sreturn";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $e = new Event(Event::KICKOFF_RETURN, Event::TD);
            $e->setTeam($team);
            $e->setOrigin($matches[0]);
            
            if(array_key_exists(1, $matches) && mb_strlen($matches[1]) > 0) {
                $e->setAuthor($this->addPlayer($matches[1], $team));
            }
            
            return $e;
        }
        
        return null;
    }

    /**
     * Converts textual extra point description to instance of ESPN\Event. Takes
     * full description, e.g. "Samson Ebukam 25 Yd Interception Return (Greg Zuerlein Kick)",
     * parses only extra point description (incl. two-point conversions) and decomposes
     * into ESPN\Event object. Some of the descriptions on ESPN lacks brackets, this
     * method works with such cases, but the first name mentioned in XP cannot more then
     * two words (e.g. "Will Fuller V run" or "Patrick Mahomes III pass to Tyreek Hill"
     * may cause inaccuracies.
     *
     * @param   string  $scoring_description   Full textual description of TD, incl. XP
     * @param   Team    $team                  Instance of Team that scored points
     * @return  Event  Instance of ESPN\Event class with decomposed info.
     */
    private function decomposeXP(string $scoring_description, Team $team): ?Event
    {
        $matches = [ ];
        // One-point conversion is good
        if (preg_match("/\(?(" . $this->name_pattern_2w .")\skick(?:\sis\sgood)?\)?/i", $scoring_description, $matches)) {
            $e = new Event(Event::KICK, Event::XP);
            $e->setTeam($team);
            $e->setOrigin($matches[0]);
            
            if (array_key_exists(1, $matches) && mb_strlen($matches[1]) > 0) {
                $e->setAuthor($this->addPlayer($matches[1], $team));
            }
            
            return $e;
        }
        
        // One-point conversion failed
        if (preg_match("/\(?(" . $this->name_pattern_2w .")\sPAT\sfailed\)?/i", $scoring_description, $matches)) {     
            $e = new Event(Event::KICK, Event::XP);
            $e->setTeam($team);
            $e->setOrigin($matches[0]);
            $e->setResult(false);
            
            if (array_key_exists(1, $matches) && mb_strlen($matches[1]) > 0) {
                $e->setAuthor($this->addPlayer($matches[1], $team));
            }
            
            return $e;
        }
        
        // Two-point conversion failed
        if (preg_match("/\(?(two-point\s(pass|run)?\s?conversion\sfailed)\)?/i", $scoring_description, $matches)) {
            $e = new Event(Event::OTHER, Event::X2P);
            $e->setTeam($team);
            $e->setOrigin($matches[0]);
            $e->setResult(false);
            return $e;
        }
        
        // Two-point pass conversion with brackets
        $pattern = "\((" . $this->name_pattern_3w . ")\spass\sto\s";
        $pattern .= "(" . $this->name_pattern_3w . ")\sfor\stwo-point\sconversion\)";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $e = new Event(Event::RECEPTION, Event::X2P);
            $e->setTeam($team);
            $e->setOrigin($matches[0]);
            
            if(array_key_exists(1, $matches) && mb_strlen($matches[1]) > 0) {
                $e->setPasser($this->addPlayer($matches[1], $team));
            }
            
            if(array_key_exists(2, $matches) && mb_strlen($matches[2]) > 0) {
                $e->setAuthor($this->addPlayer($matches[2], $team));
            }
            
            return $e;
        }
        
        // Two-point run conversion with brackets
        $pattern = "\((" . $this->name_pattern_3w . ")\srun\sfor\stwo-point\sconversion\)";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $e = new Event(Event::RUN, Event::X2P);
            $e->setTeam($team);
            $e->setOrigin($matches[0]);
            
            if (array_key_exists(1, $matches) && mb_strlen($matches[1]) > 0) {
                $e->setAuthor($this->addPlayer($matches[1], $team));
            }
            
            return $e;
        }
        
        // Two-point pass conversion with no brackets
        $pattern = "(" . $this->name_pattern_2w . ")\spass\sto\s";
        $pattern .= "(" . $this->name_pattern_3w . ")\sfor\stwo-point\sconversion";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $e = new Event(Event::RECEPTION, Event::X2P);
            $e->setTeam($team);
            $e->setOrigin($matches[0]);
            
            if (array_key_exists(1, $matches) && mb_strlen($matches[1]) > 0) {
                $e->setPasser($this->addPlayer($matches[1], $team));
            }
            
            if (array_key_exists(2, $matches) && mb_strlen($matches[2]) > 0) {
                $e->setAuthor($this->addPlayer($matches[2], $team));
            }
            return $e;
        }
        
        // Two-point run conversion with no brackets
        $pattern = "(" . $this->name_pattern_2w . ")\srun\sfor\stwo-point\sconversion";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $e = new Event(Event::RUN, Event::X2P);
            $e->setTeam($team);
            $e->setOrigin($matches[0]);
            
            if(array_key_exists(1, $matches) && mb_strlen($matches[1]) > 0) {
                $e->setAuthor($this->addPlayer($matches[1], $team));
            }
            
            return $e;
        }
        
        return null;
    }

    /**
     * Converts textual field goal description to instance of ESPN\Event
     *
     * @param   string  $scoring_description   Full textual description of FG
     * @param   Team    $team                  Instance of Team that scored points
     * @return  Event  Instance of ESPN\Event class with decomposed info.
     */
    private function decomposeFG(string $scoring_description, Team $team): ?Event
    {
        $matches = [ ];
        
        $pattern = "(" . $this->name_pattern_2w . ")\s\d{1,2}\sya?r?ds?\sfield\sgoal";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $e = new Event(Event::KICK, Event::FG);
            $e->setTeam($team);
            $e->setOrigin($matches[0]);
            
            if(array_key_exists(1, $matches) && mb_strlen($matches[1]) > 0) {
                $e->setAuthor($this->addPlayer($matches[1], $team));
            }
            
            return $e;
        }
        
        return null;
    }

    /**
     * Converts textual safety description to instance of ESPN\Event
     *
     * @param   string  $scoring_description   Full textual description of SF
     * @param   Team    $team                  Instance of Team that scored points
     * @return  Event  Instance of ESPN\Event class with decomposed info.
     */
    private function decomposeSF(string $scoring_description, Team $team): ?Event
    {
        $matches = [ ];
        
        $e = new Event(Event::OTHER, Event::SF);
        $e->setTeam($team);
        $e->setOrigin($scoring_description);
            
        return $e;
    }

    /**
     * Rare case of converting textual defensive two-points description
     * to instance of ESPN\Event
     *
     * @param   string  $scoring_description   Full textual description of D2P
     * @param   Team    $team                  Instance of Team that scored points
     * @return  Event  Instance of ESPN\Event class with decomposed info.
     */
    private function decomposeD2P(string $scoring_description, Team $team): ?Event
    {
        $matches = [ ];
        
        $pattern = "(" . $this->name_pattern_3w . ")\sdefensive\spat\sconversion";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $e = new Event(Event::OTHER, Event::D2P);
            $e->setTeam($team);
            $e->setOrigin($matches[0]);
            
            if(array_key_exists(1, $matches) && mb_strlen($matches[1]) > 0) {
                $e->setAuthor($this->addPlayer($matches[1], $team));
            }
            
            return $e;
        }
        
        return null;
    }
    
    /**
     * Adds player to list if players in $this->players as an instance
     * of ESPN\Player class or retrives one and returns it.
     *
     * Players in $this->players are stored in subarrays where first level keys
     * are team names, second level keys are player full names and variables are
     * instances of Player class, e.g.:
     * $this->players["New England Patriots"]["Tom Brady"] = \ESPN\Player();
     *
     * @param   string  $player_name   Full player name as string
     * @param   string  $team          "home" or "away" strings or instances equal to
     *                                 $this->home_team or $this->away_team
     * @return  Player  Instance of Player class with player info
     */
    private function addPlayer(string $player_name, $team): Player
    {
        if ($team === "home" || $team === $this->home_team) {
            $team = $this->getHomeTeam()->full_name;
        } else if ($team === "away" || $team === $this->away_team) {
            $team = $this->getAwayTeam()->full_name;
        } else {
            throw new ParsingException("Unknown team for player to be added");
        }
        
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

        return $player;
    }
    
    /**
     * Parses quarter header and returns one of Event constants, that
     * could be used to identify the quarter;
     *
     * @return  string  quarter identifier equal to Event::Q1/Q2/Q3/Q4/OT
     */
    private function getQuarterByName(string $name = null): string
    {
        if (mb_strlen($name) == 0) {
            return Event::Q1;
        }
        
        $quarter_header = explode(" ", $name);
        
        if (count($quarter_header) == 2 && mb_strtolower($quarter_header[1]) == "quarter") {
            switch ($quarter_header[0]) {
                case 'first':   return Event::Q1;
                case 'second':  return Event::Q2;
                case 'third':   return Event::Q3;
                case 'fourth':  return Event::Q4;
            }
        }
        
        return Event::OT;
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