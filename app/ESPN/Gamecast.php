<?php
    
namespace Robin\ESPN;

use \Exception;
use \Robin\Interfaces\ParsingEngine;
use \Robin\Exceptions\ParsingException;
use \Robin\ESPN\Team;

class Gamecast implements ParsingEngine
{
    protected $html;
    protected $methods = [ "getHomeTeam", "getAwayTeam", "getLeaders", "getScore" ];
    
    public function __construct($html)
    {
        if (!$html || !in_array(get_class($html), [ "simple_html_dom", "simple_html_dom_node"])) {
            throw new ParsingException("HTML DOM not received");
        }
        
        $this->html = $html;
    }
    
    public function getMethods(): array
    {
        return $this->methods;
    }
    
    /**
     * Getting team obkect from the page
     * @param   string   $marker    Class name in page source code (usually "home" or "away")
     *
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
     * Public shortcut for getTeam with home marker
     *
     * @return  Team                Instance of Team class
     */    
    public function getHomeTeam(): Team
    {
        return $this->getTeam("home");
    }
    
    /**
     * Public shortcut for getTeam with away marker
     *
     * @return  Team                Instance of Team class
     */  
    public function getAwayTeam(): Team
    {
        return $this->getTeam("away");
    }
    
    /**
     *
     *
     *
     */
    
    
}