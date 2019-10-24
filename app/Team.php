<?php
    
namespace Robin;

use \Exception;
use \Robin\Exceptions\ParsingException;
use \Robin\Logger;
use \Robin\Essence;

 /**
  * Class for Team entities that extends Essence
  * 
  * @package    Robin
  * @author     Yuriy Marin <yuriy.marin@gmail.com>
  */
class Team extends Essence
{
    use Logger;

    private static $postfixes = [ "State" => "SU", "A&M" => "A&M", "Southern" => "STH",
                                  "Tech" => "TU", "Force" => "FA"];
    private static $prefixes  = [ "San" , "New", "North", "Northern", "South", "Southern",
                                  "East", "Eastern", "West", "Western", "Central", "Middle",
                                  "Old", "Ole", "Notre"];

    /**
     * Class constructor
     *
     * @param   string  $language       Original language of the name variables, e.g. "en"
     * @param   string  $full_name      Full name of the team
     * @param   string  $short_name     (optional) Short name of the team
     * @param   string  $abbr           (optional) Abbreviation of the team
     */
    public function __construct(string $language, string $full_name, string $short_name = "", string $abbr = "")
    {
        $this->category = "Teams";
        parent::__construct($language);
        
        $this->setAttributes(["full_name", "short_name", "abbr", "img"]);
        
        // If both full and short names are empty, we throw exception
        if (mb_strlen(trim($full_name)) == 0 && mb_strlen(trim($short_name)) == 0) {
            throw new Exception("No name was provided");
        }
        
        if (mb_strlen(trim($full_name)) == 0) {
            $full_name = $short_name;
        }
        
        if (mb_strlen(trim($short_name)) == 0) {
            $short_name = self::makeShortName($full_name);
        }
        
        if (mb_strlen(trim($abbr)) == 0) {
            $abbr = self::makeAbbr($short_name);
        }
        
        $this->full_name = $full_name;
        $this->short_name = $short_name;
        $this->abbr = $abbr;
    }
    
    /**
     * Makes short name out of long name, e.g. "Dever Broncos" => "Denver"
     *
     * @param   string  $full_name  Full name of the team
     *
     * @return  string
     */
    public static function makeShortName(string $full_name): string
    {
        if (mb_strlen(trim($full_name)) == 0) {
            throw new Exception("No team name was provided");
        }
        
        $name_parts = explode(" ", $full_name);
        
        if (count($name_parts) == 1) {
            return $name_parts[0];
        }
        
        if (count($name_parts) == 2) {
            $short_name = $name_parts[0];
            
            if (in_array($name_parts[1], array_keys(self::$postfixes)) ||
                in_array($name_parts[0], self::$prefixes)) {
                $short_name .= " " . $name_parts[1];
            }
            
            return $short_name;
        }
        
        if (count($name_parts) == 3) {
            
            if (in_array($name_parts[2], array_keys(self::$postfixes))) {
                return $name_parts[0] . " " . $name_parts[1] . " " . $name_parts[2];
            }
            
            return self::makeShortName($name_parts[0] . " " . $name_parts[1]);
        }
        
        if(count($name_parts) % 2 == 0) {
            $short_name = "";
            
            for ($i = 0; $i < count($name_parts)/2; $i++) {
                $short_name .= $name_parts[$i] . " ";
            }
            
            if (in_array($name_parts[$i], array_keys(self::$postfixes))) {
                $short_name .= $name_parts[$i];
            }
            
            return self::makeShortName(trim($short_name));
        } else {
            return self::makeShortName($name_parts[0] . " " . $name_parts[1] . " " . $name_parts[2]);
        }
    }
    
    /**
     * Makes abbreviation out of long name, e.g. "Dever Broncos" => "DEN"
     *
     * @param   string  $full_name  Full name of the team
     *
     * @return  string
     */
    public static function makeAbbr(string $name): string
    {
        if (mb_strlen($name) == 0) {
            throw new Exception("No name was provided");
        }
        
        $name_parts = explode(" ", $name);
        
        // If name consists of one word, we just take 3 or 4 first letters of it
        if (count($name_parts) == 1) {
            if (mb_strlen($name_parts[0]) <= 4) {
                return mb_strtoupper($name_parts[0]);
            }
            
            return mb_strtoupper(mb_substr($name_parts[0], 0, 3));
        }
        
        $abbr = "";
        
        // Otherwise, shortening is composite of first letters
        if (count($name_parts) == 2) {
            $abbr = mb_strcut($name_parts[0], 0, 1);
            
            // If second word is one of typical postfixes, we take preset shortening
            foreach (self::$postfixes as $key => $shortening) {
                if ($name_parts[1] == $key) {
                    return $abbr . $shortening;
                }
            }
            
            $abbr .= mb_strcut($name_parts[1], 0, 1);
        } else {
            for ($i = 0; $i < count($name_parts)-1; $i++) {
                $abbr .= mb_strcut($name_parts[$i], 0, 1);
            }
            
            $last_word = $name_parts[$i];
            
            // If second word is one of typical postfixes, we take preset shortening
            $abbr .= self::$postfixes[$last_word] ?? mb_strcut($last_word, 0, 1);
        }
            
        return mb_strtoupper($abbr);
    }

}