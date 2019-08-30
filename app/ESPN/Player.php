<?php
    
namespace Robin\ESPN;

use \Exception;
use \Robin\Exceptions\ParsingException;
use \Robin\Logger;
use \Robin\Interfaces\ParsingEngine;

 /**
  * Class for Player entities inside ESPN
  * 
  * @package    Robin
  * @subpackage ESPN
  * @author     Yuriy Marin <yuriy.marin@gmail.com>
  */
class Player
{
    public  $first_name = null;
    public  $last_name = null;
    private $doubleword_names = [ "Ha Ha" ];
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
                                           "interceptions" => null,
                                           "pass_deflected" => null, "td" => null,
                                           "qb_hits" => null ],
                       "kick_returns" => [ "number" => null, "yards" => null, "td" => null],
                       "punt_returns" => [ "number" => null, "yards" => null, "td" => null],
                       "kicking"      => [ "fg_attempts" => null, "fg_scores" => null,
                                           "fg_longest" => null, "xp_attempts" => null,
                                           "xp_scores" => null],
                       "punting"      => [ "number" => null, "yards" => null,
                                           "longest" => null, "touchbacks" => null]
                     ];

    /**
     * Class constructor
     *
     * @param   string  $f_name     Full name or first name if $l_name is not null
     * @param   string  $l_name     (optional) Last name
     */
    public function __construct(string $f_name, $l_name = null)
    {
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
        if(mb_strlen($full_name) === 0) {
            throw new Exception("No name was provided");
        }
        
        //Run through possible double names exceptions
        foreach ($this->doubleword_names as $double_name) {
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
     * Magic function, that used to set stats. If it starts with "set" and camelCase
     * notation is used, then it got splitted into words. Words are used to find the
     * right stats name and the first argument is used as value, e.g.:
     *
     * \Robin\ESPN\Player::setRushingCarries() => $this->stas["rushing"]["carries"]
     */
    
    public function __call(string $name, array $arguments)
    {
        if (substr($name, 0, 3) == "set") {
            $variable = &$this->camelCaseToStatsVariable($name, "set");
            $variable = is_numeric($arguments[0]) ? $arguments[0] : null;
        }
        
        if (substr($name, 0, 3) == "get") {
            return $this->camelCaseToStatsVariable($name, "get");
        }
        
    }
    
    public function getStatsArray(bool $show_nulls = false): array
    {
        $ret = [ ];
        
        foreach ($this->stats as $category_name => $category_array) {
            
            $category_ret = [ ];
            
            foreach ($category_array as $index_key => $index_value) {
                if ($show_nulls == true && ($index_value === null || $index_value == 0)) {
                    $category_ret[$index_key] = 0;
                } else if ($index_value > 0) {
                    $category_ret[$index_key] = $index_value;
                }
                
            }
            
            if (count($category_ret) > 0) {
                $ret[$category_name] = $category_ret;
            }
        }
        
        return $ret;
    }
    
    private function & camelCaseToStatsVariable(string $name, string $prefix = null)
    {
        $null = null;

        if (strlen($name) == 0) {
            return $null;
        }
        
        if (strlen($prefix) > 0) {
            $name = substr($name, strlen($prefix));
        }

        $words = preg_split("/((?<=[a-z])(?=[A-Z])|(?=[A-Z][a-z]))/", $name);
        
        if (mb_strlen($words[0]) == 0) {
            array_shift($words);
        }
        
        if (count($words) < 2) {
            if (array_key_exists($words[0], $this->stats)) {
                return $this->stats[$words[0]];
            }
            
            return $null;
        }
        
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
        
        return $null;
    }
}