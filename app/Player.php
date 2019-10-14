<?php

namespace Robin;

use \Exception;
use \Robin\Exceptions\ParsingException;
use \Robin\Logger;
use \Robin\Essence;

 /**
  * Class for Players that extends Essence
  * 
  * @package    Robin
  * @author     Yuriy Marin <yuriy.marin@gmail.com>
  */
class Player extends Essence
{
    private $stats = [ "passing"      => [ "attempts" => null, "completions" => null,
                                           "yards" => null, "td" => null,
                                           "int" => null, "rating" => null ],
                       "rushing"      => [ "carries" => null, "yards" => null,
                                           "td" => null, "longest" => null ],
                       "receiving"    => [ "targets" => null, "receptions" => null,
                                           "yards" => null, "td" => null],
                       "fumbles"      => [ "lost" => null, "recovered" => null ],
                       "defensive"    => [ "tackles" => null, "solo_tackles" => null,
                                           "tackles_for_loss" => null, "sacks" => null,
                                           "int" => null,
                                           "pass_deflected" => null, "td" => null,
                                           "qb_hits" => null ],
                       "kick_returns" => [ "number" => null, "yards" => null, "td" => null],
                       "punt_returns" => [ "number" => null, "yards" => null, "td" => null],
                       "kicking"      => [ "fg_attempts" => null, "fg_scores" => null,
                                           "fg_longest" => null, "xp_attempts" => null,
                                           "xp_scores" => null ],
                       "punting"      => [ "number" => null, "yards" => null,
                                           "longest" => null, "touchbacks" => null]
                     ];
    
    /**
     * Class constructor
     *
     * @param   string  $language   Original language of the name variables, e.g. "en"
     * @param   string  $f_name     Full name or first name if $l_name is not null
     * @param   string  $l_name     (optional) Last name
     */
    public function __construct(string $language, string $f_name, $l_name = null)
    {
        $this->category = "Players";
        parent::__construct($language);
        
        $this->setAttributes(["first_name", "last_name", "first_name_genitive",
                              "last_name_genitive", "position", "number"]);
        
        if (mb_strlen($l_name) === 0) {
            $this->splitFullName($f_name);
        } else {
            $this->first_name = $f_name;
            $this->last_name = $l_name;
        }
    }

    /**
     * Splits full name into first name and last name
     *
     * @param   string  $full_name     Full name, preferably with space inside
     */
    private function splitFullName(string $full_name): void
    {
        $doubleword_names = [ "Ha Ha" ];
        
        if(mb_strlen($full_name) === 0) {
            throw new Exception("No name was provided");
        }
        
        //Run through possible double names exceptions
        foreach ($doubleword_names as $double_name) {
            if (mb_strpos($full_name, $double_name) === 0) {
                $this->first_name = $double_name;
                $this->last_name = mb_substr($full_name, mb_strlen($double_name));
                return;
            }
        }
        
        $name_parts = explode(" ", $full_name);
        
        if (count($name_parts) < 2) {
            $this->first_name = "";
            $this->last_name = $name_parts[0];
            return;
        }
        
        $this->first_name = array_shift($name_parts);
        $this->last_name = join(" ", $name_parts);
    }
    
    /**
     * Returns full name of the player in one string
     *
     * @param   bool    $include_position_and_number    include into name position
     *                                                  and number, e.g. "QB Tom Brady (#12)"
     *                                                  instead of just "Tom Brady"
     * @return  string  Full player name
     */
    public function getFullName(bool $include_position_and_number = false): string
    {
        $name = "";
        if ($include_position_and_number && mb_strlen($this->position) > 0) {
            $name .= $this->position . " ";
        }
        
        if (mb_strlen($this->first_name)) {
           $name .= $this->first_name . " "; 
        }
        
        $name .= $this->last_name;
        
        if ($include_position_and_number && mb_strlen($this->number) > 0) {
            $name .= " (#" . $this->number . ")";
        }
        return $name;
    }
    
    public function getStats(string $category_name)
    {
        if (mb_strlen(trim($category_name)) == 0) {
            throw new Exception("Stats category cannot be empty");
        }
        
        $parts = explode("_", Essence::camelCaseToUnderscore($category_name));
        if (count($parts) == 1) {
            throw new Exception("Unknown stats category");
        } 
        
        return implode(" ", $parts);
        
    }
    
    /**
     * Takes camelCased name from magic method __call, split it into elements and
     * searches through $this->stats for necessary variable. Returns reference for
     * the value or generates Exception if it is not found; 
     *
     * @param   string  $name   camelCased name from __call
     * @return  reference       referense for the found value
     */
    private function & findStatsVariable(string $name)
    {
        $words = explode("_", Essence::camelCaseToUnderscore($name));
        
        $null = null;

        if (strlen($name) == 0) {
            throw new Exception("Argument \"name\" is missing");
        }

        $words = preg_split("/((?<=[a-z])(?=[A-Z])|(?=[A-Z][a-z]))/", $name);
                
        if (count($words) == 1) {
            throw new Exception("Unknown stats category");
        } elseif (count($words) == 2) {
            if(array_key_exists($words[0], $this->stats) && array_key_exists($words[1], $this->stats[{$words[0]}])) {
                return $this->stats[{$words[0]}][{$words[1]}];
            } else {
                throw new Exception("Unknown stats category");
            }
        }
        
/*
        // indexes 0 and 1 exist in words by if statement above
        $p1 = strtolower($words[0]);
        $p2 = strtolower($words[1]);
        
        // if index 3 exist, assign it to p3
        if (array_key_exists(2, $words)) {
            $p3 = strtolower($words[2]);
        } else {
            $p3 = null;
        }
        
        // all others, if exist, got joined with "_" and concatenated to p3
        if (array_key_exists(3, $words)) {
            for ($i=3; $i<count($words); $i++) {
                $p3 .= " " . strtolower($words[$i]);
            }
                
            $p3 = str_replace(" ", "_", trim($p3));
        }
        
        if (array_key_exists($p1, $this->stats)) {
            if ($p3 === null && array_key_exists($p2, $this->stats[$p1])) {
                return $this->stats[$p1][$p2];
            } else if(array_key_exists($p2 . "_" . $p3, $this->stats[$p1])) {
                return $this->stats[$p1][$p2 . "_" . $p3];
            }        
        } else if(array_key_exists($p1 . "_" . $p2, $this->stats)) {
            if ($p3 !== false && array_key_exists($p3, $this->stats[$p1 . "_" . $p2])) {
                return $this->stats[$p1 . "_" . $p2][$p3];
            }
        }
*/
        
        throw new Exception("No stats value is found");
    }

    
    /*
    $this->getStatsList("passing");
    
    $this->getStats("KickReturnsNumber");
    $this->setStats("PassingAttempts", 5);
    */
    
    public function export()
    {
        return $this->values;
    }
    
}