<?php

namespace Robin;

use \Exception;
use \Robin\Exceptions\ParsingException;
use \Robin\Logger;
use \Robin\Essence;
use \Robin\Inflector;

 /**
  * Class for Player entities that extends Essence
  * 
  * @package    Robin
  * @author     Yuriy Marin <yuriy.marin@gmail.com>
  */
class Player extends Essence
{
    use Logger;
    
    const TRANSLATION_ID = "stats"; // id for file with terms translation
    
    protected static $default_language;
    private $translation = [];
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
     * Class constructor. Instance of player can be created with the name of a player
     * or by restoring the array from Player::export() method.
     *
     * @param   mixed   $f_name     Full name or first name if $l_name is not null.
     *                              If array is passe, it is used to restore full
     *                              object.
     * @param   string  $l_name     (optional) Last name
     */
    public function __construct($f_name, $l_name = null)
    {
        // If class doesn't have its own default language set, we take it from parent class
        if (!self::$default_language) {
            self::$default_language = parent::$default_language;
        }
        
        parent::__construct("Players");
        $this->language = self::$default_language;
        
        $this->setAttributes(["first_name", "last_name", "first_name_genitive",
                              "last_name_genitive", "position", "number"]);
        
        // If the first passed argument is array, we assume it as restoration
        // with array from export().
        if (is_array($f_name) && count($f_name) > 0) {
            // Taking stats away from passed array and then import values
            // with Essence::import() method
            if (is_array($f_name["stats"]) && count($f_name["stats"]) > 0) {
                $stats = $f_name["stats"];
            }
            
            if (isset($f_name["stats"])) {
                unset($f_name["stats"]);
            }
            
            if ($this->import($f_name)) {
                $this->stats = $stats;
            } else {
                throw new Exception("Import from array failed");
            }
        } elseif (mb_strlen($l_name) === 0) {
            $this->splitFullName($f_name);
        } else {
            $this->first_name = $f_name;
            $this->last_name = $l_name;
        }
        
        $this->id = $this->getFullName();
    }
    
    /**
     * STATIC METHOD
     * Sets the default language for all future instances of Essence.
     *
     * @param   string  $language   Default language, e.g. "en"
     *
     * @return  void         
     */    
    public static function setDefaultLanguage(string $language): void
    {
        if (strlen($language) == 0) {
            throw new Exception("Default language for Essence cannot be empty");
        }
        
        self::$default_language = $language;
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
            $category_name = Inflector::camelCaseToUnderscore($category_name);
            
            if (array_key_exists($category_name, $this->stats)) {
                $stats = $this->stats[$category_name];
            } else {
                throw new Exception("Unknown stats category \"" . $category_name . "\"");
            }
        }
        
        if (!$return_nulls) {
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
     * Returns stats variables, joined to one string with $glue
     *
     * @param   string  $glue       string to put between variables
     * @param   string  $category   (optional) Stats category. If not set,
     *                              all stats will be returned;
     * @return  string              Statis in one string
     */
    public function getJoinedStats(string $glue, string $category = ""): string
    {
        // if category is not set, it means that all stats are requested and we
        // need to run through stat categories first
        if (strlen($category) == 0) {
            $stat_categories = $this->getStatsList(false);
            $joined_stats = [ ];
            foreach ($stat_categories as $category_name => $category_values) {
                $joined_stats[] = $this->getJoinedStats($glue, $category_name);
            }
            
            return implode($glue, $joined_stats);
        }
        
        // if we have category, we run through variables and put them into string
        $stats = $this->getStatsList(false, $category);
        $joined_stats = [];
        foreach ($stats as $stat_name => $stat_value) {
            $joined_stats[] = $this->getStatString($stat_value, $stat_name);
        }
        
        return implode($glue, $joined_stats);
    }
    
    
    /**
     * Returns string of stat value and name
     *
     * @param   int       $number    Stat value
     * @param   string    $name      Basic stats name
     * @return  string               Plural form of stats variable
     */
    private function getStatString(int $number, string $name): string
    {
        // Check if translation is needed
        if($this->language == self::$default_language) {
            return $number . " " . $name;
        }
        
        // If translation is needed, we check data handler to get data
        if (!$this->data_handler) {
            throw new Exception("Please set handler with Player::setDataHandler() method to read translation data");
        }
        
        // Here and next conditional blocks: loading translation and check it existance
        if (!is_array($this->translation) || count($this->translation) == 0) {
            $this->translation = $this->data_handler->read(self::TRANSLATION_ID);
        }
        
        if(!array_key_exists($this->language, $this->translation)) {
            return $number . " " . $name;
        }
        
        if (!array_key_exists($name, $this->translation[$this->language])) {
            return $number . " " . $name;
        }
        
        // Run through rules and return accordance
        $rules_source = explode(",", $this->translation[$this->language][$name]);
        $rules = [ ];
        foreach ($rules_source as $regulation) {
            $components = explode(" ", trim($regulation));
            if (count($components) > 1) {
                $rules[$components[0]] = $components[1];
            } else {
                $rules[] = $components[0];
            }
        }
        
        return $number . " " . Inflector::pluralize($number, $rules);
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
        
        $words = explode("_", Inflector::camelCaseToUnderscore($name));

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

    /**
     * Returns all values of the Player
     *
     * @return  array          Content of $this->values
     */ 
    public function export(bool $include_stats = false): array
    {
        $ret = $this->values;
        if ($include_stats) {
            $ret["stats"] = $this->stats;
        }
        return $ret;
    }
}