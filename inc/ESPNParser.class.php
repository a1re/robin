<?php
    
namespace Inc;

use \Exception;

class ESPNParser
{
    protected $html;
    protected $supported_domains = ["espn.com", "www.espn.com", "robin.local", "robin.firstandgoal.in"];
    protected $home_team = [ ];
    protected $away_team = [ ];
    protected $score = [];
    protected $home_leaders = [];
    protected $away_leaders = [];
    protected $scoring_events = [];
    private $logger = false;
    
    const FULL_NAME = 0;
    const SHORT_NAME = 1;
    const ABBR_NAME = 2;
    
    public function __construct($url, $logger = false)
    {
        // Checking if we have SimpleHTMLDOM loaded
        if (!function_exists("file_get_html")) {
            die("Parsing function not defined");
        }
        
        // Checking if we creating object with a valid URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid URL');
        }
        $domain = parse_url($url, PHP_URL_HOST);
        
        // Checking if URL belongs to ESPN website
        if (!in_array($domain, $this->supported_domains)) {
            throw new Exception('Invalid domain');            
        }
        
        // If object is provided with Monolog logger, we use it as logging tool
        // Otherwise, $this->logger is just an array with messages
        if (is_object($logger) && get_class($logger) == "Monolog\Logger") {
            $this->logger = $logger;
        } else {
            $this->logger = [];
        }
        
        $this->html = file_get_html($url);
    }
   
    private function log($message)
    {
        if (is_array($logger)) {
           $this->logger[] = $message;
           return null;
        }
        
        $this->logger->notice($message);
    }
    
    /*
        ESPNParser::FULL_NAME for full name, etc. "Denver Broncos"
        ESPNParser::SHORT_NAME for short name, etc. "Denver"
        ESPNParser::ABBR_NAME for abbrev, etc. "DEN"
    */    
    public function getHomeTeamName($type = self::SHORT_NAME)
    {
        // To avoid unnecessary parsing, we save team name as object variable
        if (count($this->home_team) == 0) {
            $first_name = $this->html->find("div.competitors div.home a.team-name .long-name", 0);
            $last_name = $this->html->find("div.competitors div.home a.team-name .short-name", 0);
            $abbr_name = $this->html->find("div.competitors div.home a.team-name .abbrev", 0);
            
            if ($first_name != null) {
                // If block with both city and name was found
                if ($last_name != null) {
                    $this->home_team[self::FULL_NAME] = $first_name->plaintext . ' ' . $last_name->plaintext;
                }
                
                $this->home_team[self::SHORT_NAME] = $first_name->plaintext;
            }
            
            if ($abbr_name != null) {
                $this->home_team[self::ABBR_NAME] = $abbr_name->plaintext;
            }
        }
       
        if (isset($this->home_team[$type])) {
            return $this->home_team[$type];
        }
        
        $this->log("No home team name was found");
        return null;
    }
    
    /*
        ESPNParser::FULL_NAME for full name, etc. "Denver Broncos"
        ESPNParser::SHORT_NAME for short name, etc. "Denver"
        ESPNParser::ABBR_NAME for abbrev, etc. "DEN"
    */
    public function getAwayTeamName($type = self::SHORT_NAME)
    {
        // To avoid unnecessary parsing, we save team name as object variable
        if (count($this->away_team) == 0) {
            $first_name = $this->html->find("div.competitors div.away a.team-name .long-name", 0);
            $last_name = $this->html->find("div.competitors div.away a.team-name .short-name", 0);
            $abbr_name = $this->html->find("div.competitors div.away a.team-name .abbrev", 0);
            
            if ($first_name != null) {
                // If block with both city and name was found
                if ($last_name != null) {
                    $this->away_team[self::FULL_NAME] = $first_name->plaintext . ' ' . $last_name->plaintext;
                }
                
                $this->away_team[self::SHORT_NAME] = $first_name->plaintext;
            }
            
            if ($abbr_name != null) {
                $this->away_team[self::ABBR_NAME] = $abbr_name->plaintext;
            }
        }
       
        if (isset($this->away_team[$type])) {
            return $this->away_team[$type];
        }
        
        $this->log("No away team name was found");
        return null;
        
    }
   
}