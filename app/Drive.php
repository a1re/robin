<?php
    
namespace Robin;

use \Exception;
use \Robin\Logger;
use \Robin\Inflector;
use \Robin\Keeper;
use \Robin\GameTerms;
use \Robin\Play;

 /**
  * Class for Drives entities
  * 
  * @package    Robin
  * @author     Yuriy Marin <yuriy.marin@gmail.com>
  */
class Drive extends GameTerms
{
    use Logger;
    
    const TRANSLATION_ID = "gameterms"; // id for file with terms translation
    private static $default_language = "en";
    
    private $language;
    private $translation = [];
    private $values = [
        "is_scoring_drive" => false,
        "home_score" => null,
        "away_score" => null,
        "possessing_team" => null,
        "defending_team" => null,
        "quarter" => null,
        "ending" => null,
        "plays" => [ ]
    ];
    
    /**
     * Class constructor. Can be used in two different ways — by $possessing_team
     * and $defending_team or by passing array of variables exported by method 
     * Drive::export(), e.g.:new Drive($exported_values);
     *
     * @param   string  $possessing_team    Id of the team that has posession on
     *                                      the beginning of the drive
     * @param   string  $defending_team     Id of the team that acts as defening 
     *                                      on the beginning of the drive
     */
    public function __construct($possessing_team = "", string $defending_team = "")
    {
        $this->values["source_language"] = self::$default_language;
        $this->play = self::$default_language;
        
        if (is_array($possessing_team)) {
            $import = $possessing_team;
            
            if (array_key_exists("possessing_team", $import)) {
                $possessing_team = $import["possessing_team"];
                unset($import["play_type"]);
            }
            
            if (array_key_exists("defending_team", $import)) {
                $defending_team = $import["defending_team"];
                unset($import["defending_team"]);
            }   
        } else if (!is_string($possessing_team)) {
            throw new Exception("Possessing team must be defined as ID of a valid non-empty string");
        }
        
        $this->setPossessingTeam($possessing_team);
        $this->setDefendingTeam($defending_team);
        
        if (isset($import) && count($import) > 0) {
            foreach ($import as $name=>$value) {
                if ($value === null) {
                    continue;
                }
                
                if ($name == "plays" && is_array($value)) {
                    foreach ($value as $play) {
                        $this->addPlay(new Play($play));                        
                    }
                    continue;
                }
                
                $method_name = Inflector::underscoreToCamelCase("set_" . $name);
                
                if (method_exists($this, $method_name)) {
                    $this->{$method_name}($value);
                } else if (array_key_exists($name, $this->values)){
                    $this->values[$name] = $value;
                }
            }
        }
    }
    
    /**
     * STATIC METHOD
     * Sets the default language for all future instances of Drive.
     *
     * @param   string  $language   Default language, e.g. "en"
     *
     * @return  void         
     */    
    public static function setDefaultLanguage(string $language): void
    {
        if (strlen(trim($language)) == 0) {
            throw new Exception("Default language of Drive cannot be empty");
        }
        
        self::$default_language = $language;
    }
    
    /**
     * Sets active language of drive.
     *
     * @param   string  $language               Language of the name variables, e.g. "en"
     * @paeam   bool    $use_exising_values     Set to true, if 
     *
     * @return  void         
     */
    public function setLanguage(string $language, bool $use_existing_values = false): void
    {
        if (strlen(trim($language)) == 0) {
            throw new Exception("Language of Drive cannot be empty");
        }
        if (is_array($this->values["plays"]) && count($this->values["plays"]) > 0) {
            foreach ($this->values["plays"] as $i=>$play) {
                if (is_object($play)) {
                    $reflect = new \ReflectionClass($play);
                    if ($reflect->getShortName() === 'Play') {
                        $this->values["plays"][$i]->setLanguage($language, $use_existing_values);
                        continue;
                    }
                }
            }
        }
        $this->language = $language;
    }
    
    /**
     * Returns current language of drive.
     *
     * @return  string     Language value
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Checks if drive has values in defined language.
     *
     * @param   string  $language   Language name to ve checked, e.g. "en". Case matters
     * @param   string  $attrubute  (optional) Name of the attribute to be checked.
     *                              If is set, then method checks existance of
     *                              attribute, not just language.
     * @return  bool                True if translation exists, False if not.
     */
    public function isTranslated($language, string $attribute = null): bool
    {
        // If set set languge equals to source language, then it's not translated at all
        if ($this->language == $this->values["source_language"]) {
            return false;
        }
        
        // If $translation property doesn't have $language value or $language is not equal
        // to language property of the object, then language setting is not loaded
        if (!array_key_exists($language, $this->translation) || $this->language != $language) {
            return false;
        }
        
        if ($attribute !== null) {
            if (!$this->data_handler) {
                throw new Exception("Please set handler with Drive::setDataHandler() method to read translation data");
            }
            
            if (array_key_exists($attribute, $this->translation[$language])) {
                return true;
            }
            return false;
        }
        return true;
    }
    
    /**
     * Set hadler for reading translation of the play
     *
     * @param   Keeper  $data_handler   Keeper object for storing data
     *
     * @return  void         
     */
    public function setDataHandler(Keeper $data_handler): void
    {
        $this->data_handler = $data_handler;
        $this->translation = $this->data_handler->read(self::TRANSLATION_ID);
        if (is_array($this->values["plays"]) && count($this->values["plays"]) > 0) {
            foreach ($this->values["plays"] as $i=>$play) {
                if (is_object($play)) {
                    $reflect = new \ReflectionClass($play);
                    if ($reflect->getShortName() === 'Play') {
                        $this->values["plays"][$i]->setDataHandler($data_handler);
                        continue;
                    }
                }
            }
        }
    }
    
    /**
     * Sets ending of the drive
     *
     * @param   string  $ending     Type of drive ending, must be equal to one of
     *                              preset ending types in Robin\GameTerms::DRIVE_ENDINGS
     */
    public function setEnding(string $ending): void
    {
        $ending = trim($ending);
        
        if (strlen($ending) == 0) {
            throw new Exception("Ending type cannot be empty");
        }
        
        if (in_array($ending, self::DRIVE_ENDINGS)) {
            $this->values["ending"] = $ending;
        } else {
            throw new Exception("Unknown drive ending: \"" . $ending . "\"");
        }
    }
    
    /**
     * Public method for setting score of the drive — both home and away
     *
     * @param   int    $home_score   Home team score by the end of the drive
     * @param   int    $away_score   Away team score by the end of the drive
     */
    public function setScore(int $home_score, int $away_score): void
    {
        $this->setHomeScore($home_score);
        $this->setAwayScore($away_score);
    }

    /**
     * Internal method for setting home_score value
     *
     * @param   int    $home_score   Home team score by the end of the drive
     */
    private function setHomeScore(int $home_score): void
    {
        if ($home_score < 0) {
            throw new Exception("Home score cannot be negative");
        }
        
        $this->values["home_score"] = $home_score;
    }

    /**
     * Internal method for setting away_score value
     *
     * @param   int    $away_score   Away team score by the end of the drive
     */    
    private function setAwayScore(int $away_score): void
    {
        if ($away_score < 0) {
            throw new Exception("Away score cannot be negative");
        }
        
        $this->values["away_score"] = $away_score;
    }

    /**
     * Public method for setting possessing team of the drive
     *
     * @param   string    $possessing_team   ID of the posessing team
     */
    public function setPossessingTeam(string $possessing_team): void
    {
        if (strlen(trim($possessing_team)) == 0) {
            throw new Exception("Possessing team ID cannot be empty");
        }
        
        $this->values["possessing_team"] = $possessing_team;
    }

    /**
     * Public method for setting defending team of the drive
     *
     * @param   string    $defending_team   ID of the posessing team
     */
    public function setDefendingTeam(string $defending_team): void
    {
        if (strlen(trim($defending_team)) == 0) {
            throw new Exception("Defending team ID cannot be empty");
        }
        
        $this->values["defending_team"] = $defending_team;
    }
    
    /**
     * Sets ending point of the drive
     *
     * @param   string    $quarter   Quarter, must be equal to one of quarters
     *                               constants in Robin\GameTerms
     */
    public function setQuarter(string $quarter): void
    {
        if (!in_array($quarter, [self::Q1, self::Q2, self::Q3, self::Q4, self::OT])) {
            throw new Exception("Incorrect quarter \"" . $quarter . "\"");            
        }
        
        $this->values["quarter"] = $quarter;
    }

    /**
     * Sets play as a scoring play
     *
     * @param   boolean     $defender   (optional) True if scoring, false if not
     */
    public function setResult(bool $is_scoring_drive): void
    {
        $this->values["is_scoring_drive"] = $is_scoring_drive;
    }
    
    /**
     * Adds play to drive
     *
     * @param   Play    $play   Object of Robin\Play
     */
    public function addPlay(Play $play):void
    {
        $this->values["plays"][] = $play;
    }
    
    /**
     * Retrieves play from massibe of plays by its number
     *
     * @param   int    $i   (optional) Number of the play starting from 1.
     *                      If nothing is set, return last one
     */
    public function getPlay($i = null): ?Play
    {
        if ($i == null) {
            $i = count($this->values["plays"]);
        }
        if (is_int($i) && $i>0) {
           if (array_key_exists($i-1,  $this->values["plays"])) {
               return $this->values["plays"][$i-1];
           } 
        }
        return null;
    }
    
    /**
     * Service function to return team and translate it if needed. Publicly used via
     * shortcuts getPossessingTeam() and getDefendingTeam()
     *
     * @param   string      $team       Kind of the team ("possessing_team" or "defending_team")
     * @param   boolean     $abbr       (optional) Set to true if team name should be
     *                                  returned in Abbr.
     * @return  string                  Team name or null
     */
    private function getTeam(string $team_type, $abbr = false) {
        if (!(array_key_exists($team_type, $this->values) || strlen(trim($this->values[$team_type])) == 0)) {
            return null;
        }
        
        if ($this->isTranslated($this->language)) {
            if (!$this->data_handler) {
                throw new Exception("Please set handler with Drive::setDataHandler() method to read translation data");
            }
            $team = new Team($this->values[$team_type]);
            $team->setDataHandler($this->data_handler);
            $team->setId($team->full_name);
            $team->read();
            if ($team->isTranslated("ru")) {
                $team->setLanguage("ru", true);
            }
            if ($abbr) {
                return $team->abbr;                
            }
            return $team->full_name;
        }
        
        return $this->values[$team_type];       
    }
    
    public function getPossessingTeam(bool $abbr = false): ?string { return $this->getTeam("possessing_team", $abbr); }
    public function getDefendingTeam(bool $abbr = false): ?string { return $this->getTeam("defending_team", $abbr); }
    
    /**
     * Magic method for to acceess private properties. Properties are accessed
     * via method with "get" prefix and property name in camelCase, e.g.
     * $this->values["ending"] is accessed via method Drive::getEnding();.
     * Properties with names starting with can be accessed withot get,
     * e.g. Drive::isScoringDrive() for $this->values["is_scoring_drive"];
     */
    public function __call($name, $arguments)
    {
        if (mb_substr($name, 0, 3) == "get") {
            $property = Inflector::camelCaseToUnderscore(mb_substr($name, 3));
            
            if (!array_key_exists($property, $this->values)) {
                throw new Exception("Call to undefined method " . $name . "() in Robin\Drive");
            }
            
            if ($this->isTranslated($this->language, $this->values[$property])) {
                if (!$this->data_handler) {
                    throw new Exception("Please set handler with Drive::setDataHandler() method to read translation data");
                }
                return $this->translation[$this->language][$this->values[$property]];
            }
            
            return $this->values[$property];
        } else if (mb_substr($name, 0, 2) == "is") {
            $property = Inflector::camelCaseToUnderscore($name);
            
            if (!array_key_exists($property, $this->values)) {
                throw new Exception("Call to undefined method " . $name . "() in Robin\Drive");
            }
            
            return $this->values[$property];
        } else {
            throw new Exception("Call to undefined method " . $name . "() in Robin\Drive");
        }
    }

    
    /**
     * Exports all non-null instance variables into array. This array can be user
     * in class constructor to restore object on property list.
     *
     * @return  array   List of object properties
     */
    public function export(): array
    {
        $values = $this->values;
        if (is_array($values["plays"]) && count($values["plays"]) > 0) {
            foreach ($values["plays"] as $i=>$play) {
                if (is_object($play)) {
                    $reflect = new \ReflectionClass($play);
                    if ($reflect->getShortName() === 'Play') {
                        $values["plays"][$i] = $play->export();
                        continue;
                    }
                }
                unset ($values["plays"][$i]);
            }
        }
        return $values;
    }
}