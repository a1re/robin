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
    
    /**
     * Returns stats list. If category not set, return all stats.
     *
     * @param   bool    $return_nulls    (optional) if set to true, returns stats
     *                                   indexes even if they are null. False is
     *                                   by default and returns only meaningful
     *                                   variables.
     * @param   string  $category_name   (optional) camelCased name of stats
     *                                   category. Empty value returns all categories.
     * @return  mixed                    Int, Float or null
     */
    public function getStatsList(bool $return_nulls = false, string $category_name = ""): array
    {
        if (mb_strlen(trim($category_name)) == 0) {
            $stats = $this->stats;
        } else {
            $category_name = Essence::camelCaseToUnderscore($category_name);
            
            if (array_key_exists($category_name, $this->stats)) {
                $stats = $this->stats[$category_name];
            } else {
                throw new Exception("Unknown stats category \"" . $category_name . "\"");
            }
        }
        
        if ($return_nulls) {
            $stats = $this->removeNulls($stats);
        }
        return $stats;
    }
    
    /**
     * Sets stat index. Name is passed in camelCase. Generates Exception if
     * it is not found, or passed value is not numeric.
     *
     * @param   string  $name   camelCased name (e.g. "PassingAttempts")
     * @param   numeric $value  Numeric value of the index
     * @return  void
     */
    public function setStats(string $name, $value): void
    {
        if (mb_strlen(trim($name)) == 0) {
            throw new Exception("Stats category cannot be empty");
        }
        
        if (!is_numeric($value)) {
            throw new Exception("Stats value must be numeric");
        }
        
        $variable = &$this->findStatsVariable($name);
        $variable = $value;
    }
    
    /**
     * Returns stats variable by camelCased name or generates Exception if
     * it is not found; 
     *
     * @param   string  $name   camelCased name (e.g. "PassingAttempts")
     * @return  mixed           Int, Float or null
     */
    public function getStats(string $name)
    {
        if (mb_strlen(trim($name)) == 0) {
            throw new Exception("Stats category cannot be empty");
        }
        
        $variable = $this->findStatsVariable($name);
        return $variable;
    }
    
    /**
     * Takes camelCased name of stats value and returns reference for
     * the value or generates Exception if it is not found; 
     *
     * @param   string  $name   camelCased name (e.g. "PassingAttempts")
     * @return  reference       referense for the found value
     */
     
    private function & findStatsVariable(string $name)
    {
        $null = null; // Only variable references can be returned by reference (&),
                      // so we need $null in case we found nothing.
        
        if (strlen($name) == 0) {
            throw new Exception("Argument \"name\" is missing");
        }
        
        $words = explode("_", Essence::camelCaseToUnderscore($name));

        if (count($words) == 1) {
            throw new Exception("Unknown stats index \"" . $name . "\"");
        } elseif (count($words) == 2) {
            if(array_key_exists($words[0], $this->stats) && array_key_exists($words[1], $this->stats[$words[0]])) {
                return $this->stats[$words[0]][$words[1]];
            } else {
                throw new Exception("Unknown stats index \"" . $name . "\"");
            }
        }
        
        for ($i=0; $i<count($words)-1; $i++) {
            $category_array = [];
            $subcategory_array = [];

            $category_array[] = $words[0];
            for ($j=1; $j<=$i; $j++) {
                $category_array[] = $words[$j];
            }
            for ($y=$j; $y<count($words); $y++) {
                $subcategory_array[] = $words[$y];
            }
            
            $category_name = implode("_", $category_array);
            $subcategory_name = implode("_", $subcategory_array);
            
            if(array_key_exists($category_name, $this->stats) && array_key_exists($subcategory_name, $this->stats[$category_name])) {
                return $this->stats[$category_name][$subcategory_name];
            }
            
        }
        
        throw new Exception("Unknown stats index \"" . $name . "\"");
    }

    /**
     * Recursively run through array and keep only values, that are not NULL and 0
     *
     * @param   array   $arr    Array to be cleaned
     * @return  array   Array without null values
     */
    private function removeNulls(array $arr): array
    {
        foreach ($arr as $key=>$value) {
            if (is_array($value)) {
                $arr[$key] = $this->removeNulls($value);
                
                if (count($arr[$key]) == 0) {
                    unset($arr[$key]);
                }
            } else {
                if ($value === null || $value == 0) {
                    unset($arr[$key]);
                }
            }
        }
        return $arr;
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