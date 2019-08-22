<?php
    
namespace Robin\ESPN;

use \Exception;

class ESPNGamecast
{
    protected $html;
    protected $team_name = [ ];
    protected $statistic_leader = [ ];
    protected $score = [ ];
    protected $quarters = [ ];
    protected $scoring_events = [ ];
    private $logger = false;
    
    const FULL_NAME = 0;
    const SHORT_NAME = 1;
    const ABBR_NAME = 2;
    
    const HT = "ht";
    const AT = "at";
    
    const PASSING = "pass";
    const RUSHING = "rush";
    const RECEIVING = "reception";
    
    const Q1 = "q1";
    const Q2 = "q2";
    const Q3 = "q3";
    const Q4 = "q4";
    const OT = "OT";
    
    public function __construct($html)
    {
        if (!$html || !in_array(get_class($html), [ "simple_html_dom", "simple_html_dom_node"])) {
            throw new Exception("HTML DOM not received");
        }
        
        $this->html = $html;
    }
    
    /**
     * Getting team name from the page
     * @param const $team — ESPNParser::HT for home team and ESPNParser::AT for away
     * @param const $type — ESPNParser::FULL_NAME for full name, etc. "Denver Broncos"
                            ESPNParser::SHORT_NAME for short name, etc. "Denver"
                            ESPNParser::ABBR_NAME for abbrev, etc. "DEN"
    */
    public function getTeamName($team = self::HT, $type = self::SHORT_NAME)
    {
        if ($team == self::HT) {
            $marker = "home";
            $t = self::HT;
        } else if ($team == self::AT) {
            $marker = "away"; 
            $t = self::AT;
        } else {
            throw new Exception("Unknown team name to be parsed");
        }
        
        // Taking team names from HTML
        $first_name = $this->html->find("div.competitors div." . $marker . " a.team-name .long-name", 0);
        $last_name = $this->html->find("div.competitors div." . $marker . " a.team-name .short-name", 0);
        $abbr_name = $this->html->find("div.competitors div." . $marker . " a.team-name .abbrev", 0);
        
        if ($first_name != null) {
            // If block with both city and name was found
            if ($last_name != null) {
                $this->team_name[$t][self::FULL_NAME] = $first_name->plaintext . ' ' . $last_name->plaintext;
            }
                
            $this->team_name[$t][self::SHORT_NAME] = $first_name->plaintext;
        }
            
        if ($abbr_name != null) {
            $this->team_name[$t][self::ABBR_NAME] = $abbr_name->plaintext;
        }
        
        // Returning the requested name if it was found
        if (isset($this->team_name[$t][$type])) {
            return $this->team_name[$t][$type];
        } else {
            throw new Exception("Requested team name was not found");
        }
    }
    
    
    
    /**
     * Shortcut of getTeamName for home team
     * @param const $type — ESPNParser::FULL_NAME for full name, etc. "Denver Broncos"
                            ESPNParser::SHORT_NAME for short name, etc. "Denver"
                            ESPNParser::ABBR_NAME for abbrev, etc. "DEN"
    */
    public function getHomeTeamName($type = self::SHORT_NAME)
    {
        // To avoid unnecessary parsing, we save team name as object variable
        if (count($this->team_name[self::HT]) == 0) {
            return $this->getTeamName(self::HT, $type);
        }
       
        if (isset($this->team_name[self::HT][$type])) {
            return $this->team_name[self::HT][$type];
        }
        
        $this->log("No home team name was found");
        return null;
    }
    
    /**
     * Shortcut of getTeamName for away team
     * @param const $type — ESPNParser::FULL_NAME for full name, etc. "Denver Broncos"
                            ESPNParser::SHORT_NAME for short name, etc. "Denver"
                            ESPNParser::ABBR_NAME for abbrev, etc. "DEN"
    */    
    public function getAwayTeamName($type = self::SHORT_NAME)
    {
        // To avoid unnecessary parsing, we save team name as object variable
        if (count($this->team_name[self::AT]) == 0) {
            return $this->getTeamName(self::AT, $type);
        }
       
        if (isset($this->team_name[self::AT][$type])) {
            return $this->team_name[self::AT][$type];
        }
        
        $this->log("No home team name was found");
        return null;
    }
    
    /**
     * Getting leader name and stats
     * @param const $team — ESPNParser::HT for home team and ESPNParser::AT for away
     * @param const $type — ESPNParser::PASSING for passing stats
                            ESPNParser::RUSHING for run stats
                            ESPNParser::RECEIVING for catching stats
    */
    public function getLeader($team = self::HT, $type = self::PASSING)
    {
        // Searching for leaders block in page source
        $tag = $this->html->find("div[data-module=teamLeaders]",0);
        
        if (!$tag) {
            $this->log("No leaders block was found");
            return null;
        }
        
        // Setting the keys for parsing: home/away
        if ($team == self::HT) {
            $marker = "home";
            $t = self::HT;
        } else if ($team == self::AT) {
            $marker = "away"; 
            $t = self::AT;
        } else {
            throw new Exception("Unknown team to be parsed");
        }
        
        // Setting the keys for parsing: stats type
        if ($type == self::PASSING) {
            $key = "passingYards";            
        } else if ($type == self::RUSHING) {
            $key = "rushingYards";
        } else if ($type == self::RECEIVING) {
            $key = "receivingYards";
        } else {
            throw new Exception("Unknown stat to be parsed");
        }
        
        $player_name_query = "div[data-stat-key=" . $key . "] ." . $marker . "-leader .player-name";
        $player_name_tag = $tag->find($player_name_query, 0);
        
        if ($player_name_tag) {
            // Player name was found
            $player_name = $player_name_tag->getAttribute("title");
        } else {
            $player_name = null;
        }
        
        $player_stat_query = "div[data-stat-key=" . $key . "] ." . $marker . "-leader .player-stats";
        $player_stat_tag = $tag->find($player_stat_query, 0);
        
        if ($player_stat_tag) {
            // Player stat was found
            $player_stat = $player_stat_tag->plaintext;
        } else {
            $player_stat = null;
        }
        
        //Parsing player stat
        if ($player_stat) {
            
            $player_stat = str_replace("&nbsp;", " ", $player_stat); // remove all unnecessary spaces
            $player_stat = str_replace("&nbsp", " ", $player_stat); // for some reason, &nbsp without ";" sometimes appear
            $player_stat = preg_replace("~[^\w -]+~", "", $player_stat); // All other non-word characters
            
            $stats = []; // collector for parsed stats
            $matches = []; // array for matches
            
            // Passing
            preg_match('/([0-9]{1,2})-([0-9]{1,2}),/', $player_stat, $matches);
            if ($matches[0]) {
                $stats["pass"] = [ "comp" => $matches[1], "attempts" => $matches[2] ];
            }            
            
            // Yards
            preg_match('/([0-9]{1,3}) yds?/i', $player_stat, $matches);
            if ($matches[0]) {
                $stats["yds"] = $matches[1];
            }
            
            // Touchdowns
            preg_match('/([0-9]{1,2}) td/i', $player_stat, $matches);
            if ($matches[0]) {
                $stats["td"] = $matches[1];
            }
            
            // Interceptions
            preg_match('/([0-9]{1,2}) int/i', $player_stat, $matches);
            if ($matches[0]) {
                $stats["int"] = $matches[1];
            }
            
            // Carries
            preg_match('/([0-9]{1,2}) car/i', $player_stat, $matches);
            if ($matches[0]) {
                $stats["carries"] = $matches[1];
            }
            
            // Carries
            preg_match('/([0-9]{1,2}) rec/i', $player_stat, $matches);
            if ($matches[0]) {
                $stats["receptions"] = $matches[1];
            }
            
            // If stats is successfully serialized, we bind it as array with origin
            if (count($stats)) {
                $stats["origin"] = $player_stat;
                $player_stat = $stats;
            }
        }
        
        if ($player_stat && $player_name) {
            return [ "player_name" => $player_name, "stats" => $player_stat];
        } else {
            $this->log("No player stats was found (" . $team . ", " . $type . ")");
            return null;
        }   
    }
    
    /**
     * Getting all ledaers stats — shortcut for getLeader for all stats at once
    */
    public function getLeaders()
    {
        if (count($this->leaders) == 0) {
            $this->leaders = [
                self::HT => [
                    self::PASSING => $this->getLeader(self::HT, self::PASSING),
                    self::RUSHING => $this->getLeader(self::HT, self::RUSHING),
                    self::RECEIVING => $this->getLeader(self::HT, self::RECEIVING)
                ],
                self::AT => [
                    self::PASSING => $this->getLeader(self::AT, self::PASSING),
                    self::RUSHING => $this->getLeader(self::AT, self::RUSHING),
                    self::RECEIVING => $this->getLeader(self::AT, self::RECEIVING)
                ]
            ];
        }

        return $this->leaders;
    }
    
    public function getScoreEvents()
    {
        $scoring_summary = $this->html->find("div[data-module=scoringSummary] div.scoring-summary > table tbody",0);
        
        if (!$scoring_summary) {
            return null;
        }
        
        $scoring_summary = $scoring_summary->find("tr");
        $current_quarter = Q1;
        
        /*
         * The idiea here is to iterate throue every row of the table. If table
         * row has "highlight" class and THs iside, it's the internal header
         * with the name of the quarter. Otherwise, it's a scoring row.
         */
        
        foreach ($scoring_summary as $e) {
            if(!is_object($e)) {
                $this->log("Row is not an object");
                continue;
            }
            
            // If current row has "highlight" class, it's identifier of the quarter
            if ($e->getAttribute("class") == "highlight") { 
                $quarter_header = $e->find("th.quarter", 0);
                
                if (!$quarter_header) {
                    $this->log("Row is not an object");
                    continue;
                }
                
                // Identifying the current quater
                if(count($quarter_header) == 2 && $quarter_header[1] == "quarter") {
                    switch ($quarter_header[0]) {
                        case 'first':
                            $current_quarter = Q1;
                            break;
                        case 'second':
                            $current_quarter = Q2;
                            break;
                        case 'third':
                            $current_quarter = Q3;
                            break;
                        case 'fourth':
                            $current_quarter = Q4;
                            break;
                    }
                } else {
                     //if second word isn't "quarter", it's overtime
                    $current_quarter = OT;
                }
                
            } else {
                $scoring_event = $this->getScoringEventDescription($current_quarter, $e);
                
                if($scoring_event) {
                    $this->scoring_events[] = $scoring_event;
                }
            }
        } 
    }
        
    /*
     * While parsing scoring row, we're filling the array with data. Every
     * item is an array itself with structure:
     * 
     * 
     * 
     * 
     * 
     */
    private function getScoringEventDescription($quarter, $tag)
    {
        //sdf   
    }
}