<?php
    
namespace Inc;

use \Exception;

class ESPNParser
{
    protected $html;
    protected $supported_domains = ["espn.com", "www.espn.com", "robin.local", "robin.firstandgoal.in"];
    protected $home_team_name;
    protected $away_team_name;
    protected $home_team_ticker;
    protected $away_team_ticker;
    protected $score = [];
    protected $home_leaders = [];
    protected $away_leaders = [];
    protected $scoring_events = [];
    
    public function __construct($url)
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
        
        $this->html = file_get_html($url);
   }
   
   public function getHomeTeamName($is_short = false)
   {
       if (mb_strlen($this->home_team_name)) {
           return $this->home_team_name;
       }
       
       
   }
   
   
   public function getAwayTeamName()
   {
       
   }
   
}