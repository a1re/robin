<?php
    
namespace Robin\ESPN;

use \Exception;
use \Robin\Exceptions\ParsingException;
use \Robin\Logger;
use \Robin\Translate;
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
    use Logger;
    use Translate;
    
    public  $first_name = null;
    public  $last_name = null;
    public  $first_name_genitive = null;
    public  $last_name_genitive = null;
    public  $position = null;
    public  $number = null;
    public  $language;
    public  $source_language;
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
        if (mb_strlen($language) == 0) {
            throw new Exception("No language set for player");
        }
        
        if (mb_strlen($l_name) === 0) {
            $this->splitFullName($f_name);
        } else {
            $this->first_name = $f_name;
            $this->last_name = $l_name;
        }
        
        $this->language = $language;
        $this->source_language = $language;
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
     * Magic function for getters and setter for the stats, that allows to call
     * $this->stats properties with cameCase notation, e.g. $this->setRushingCarries(valuer)
     * for setting $this->stats["rushing"]["carries"] or $this->getDefensiveTacklesForLoss()
     * for accessing $this->stats["defensive"]["tackles_for_loss"]. Getter also
     * can return group of stats, e.g. $this->getPassingStats() for all passing
     * indexes and even $this->getStats() for all stats.
     *
     * Setter accept only numeric arguement:
     * @param   numeric     $value      Stats value
     *
     * Getter optionaly accept bolean argument
     * @param   bool        $show_nulls True if values in array should exclude nulls and 0
     *
     * @return  mixed       Array for group of stats, numeric for particular index
     */
    public function __call(string $name, array $arguments)
    {
        // Identifying setter function by "set" prefix
        if (substr($name, 0, 3) == "set") {
            try {
                $variable = &$this->camelCaseToStatsVariable($name, "set");
                $variable = is_numeric($arguments[0]) ? $arguments[0] : null;
            } catch (Exception $e) {
                // Do nothing
            }
        }
        
        // Identifying getter function by "get" prefix
        if (substr($name, 0, 3) == "get") {
            // We cut out "Stats" postfix to make clear name method like getPassingStats work
            if (strtolower(substr($name, -5)) == "stats") {
                $name = substr($name, 0, -5);
            }
            
            // If an argument is passed and is true, we show all values, incl.
            // nulls and 0, otherwise only those with numbers
            try {
                if (array_key_exists(0, $arguments) && $arguments[0] == true) {
                    return $this->camelCaseToStatsVariable($name, "get");
                } else {
                    $stats = $this->camelCaseToStatsVariable($name, "get");
                    
                    // Recursive nulls removal
                    if (is_array($stats)) {
                        $stats  = $this->removeNulls($stats);
                    }
                    
                    return $stats;
                }
            } catch (Exception $e) {
                // Do nothing
                return null;
            }
        }
    }
    
    /**
     * Takes camelCased name from magic method __call, split it into elements and
     * searches through $this->stats for necessary variable. Returns reference for
     * the value or generates Exception if it is not found; 
     *
     * @param   string  $name   camelCased name from __call
     * @param   string  $prefix (optional) prefix, e.g. "set" or "get"
     * @return  reference       referense for the found value
     */
    private function & camelCaseToStatsVariable(string $name, string $prefix = null)
    {
        $null = null;

        if (strlen($name) == 0) {
            throw new Exception("Argument \"name\" is missing");
        }
        
        if (strlen($prefix) > 0) {
            $name = substr($name, strlen($prefix));
        }

        $words = preg_split("/((?<=[a-z])(?=[A-Z])|(?=[A-Z][a-z]))/", $name);
        
        if (mb_strlen($words[0]) == 0) {
            array_shift($words);
        }
        
        if (count($words) == 0) {
            return $this->stats;
        }
        
        if (count($words) == 1) {
            $key = strtolower($words[0]);
            if (array_key_exists($key, $this->stats)) {
                return $this->stats[$key];
            }
            throw new Exception("No stats section is found");
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
        
        throw new Exception("No stats value is found");
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
     * Returns clipped name of the player in one string
     *
     * @return  string  Player name with first name clipped to one letter, e.g. "T. Brady"
     */
    public function getClippedName(): string
    {
        $first_letter = mb_substr($this->first_name, 0, 1);
        if (mb_strlen($first_letter) > 0) {
             $first_letter .= ". ";
        }
        return $first_letter . $this->last_name;
    }
    
    /**
     * Return list of translatable attributes of a Player object
     *
     * @return  array   List of attributes names.
     */
    public function getAttributes(): array
    {
        return ["first_name", "last_name", "first_name_genitive",
                "last_name_genitive", "position", "number"];
    }

    /**
     * Return id if the translaion object
     *
     * @return  string   translation id
     */
    public function getId(): string
    {
        $id = "";
        
        if ($this->isTranslated()) {
            if (isset($this->translations[$this->source_language]) && is_array($this->translations[$this->source_language])) {
               $id = $this->translations[$this->source_language]["first_name"];
               $id = trim($id . " " . $this->translations[$this->source_language]["last_name"]);
            }
        }
        
        if (mb_strlen($id) == 0) {
            $id = $this->first_name;
            $id = trim($id . " " . $this->last_name);
        }
        
        if (mb_strlen($id) == 0) {
            $id = substr(str_shuffle(MD5(microtime())), 0, 10);
        }
        
        return $id;
    }
    
    /**
     * Check if object has translation
     *
     * @return  bool   true if translated, false if not
     */
    public function isTranslated(): bool
    {
        if ($this->language != $this->source_language) {
            return true;
        } else {
            return false;
        }
    }
}