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
    private $locale;
    private $translations = [];
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
     * @param   Team    $possessing_team    Team (as object) that has posession
     *                                      on the beginning of the drive
     * @param   Team    $defending_team     Team (as object) that acts as defening
     *                                      on the beginning of the play
     */
    public function __construct($possessing_team, $defending_team = null)
    {
        $this->language = self::$default_language;
        $this->locale = self::$default_language;
        
        if (is_array($possessing_team)) {
            $import = $possessing_team;
            
            $import = $play_type;
            
            if (array_key_exists("language", $import)) {
                $this->language = $import["language"];
                unset($import["language"]);
            }
            
            if (array_key_exists("locale", $import)) {
                $this->locale = $import["locale"];
                unset($import["locale"]);
            }
            
            if (array_key_exists("possessing_team", $import) && is_array($import["possessing_team"])) {
                $possessing_team = new Team($import["possessing_team"]);
                unset($import["possessing_team"]);
            }
            
            if (array_key_exists("defending_team", $import) && is_array($import["defending_team"])) {
                $defending_team = new Team($import["defending_team"]);
                unset($import["defending_team"]);
            }
        } else if (!is_a($possessing_team, '\Robin\Team')) {
            throw new Exception("Possessing team must be valid Team object");
        }
        
        $this->setPossessingTeam($possessing_team);
        $this->setDefendingTeam($defending_team);
        
        if (isset($import)) {
            $this->import($import);
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
     * @param   string  $locale         Locale of the name variables, e.g. "en_US"
     * @return  bool                    True if locale was set, false if not
     */
    public function setLocale(string $locale, bool $use_existing_values = false): bool
    {
        if (strlen(trim($locale)) == 0) {
            throw new Exception("Locale of Drive cannot be empty");
        }
        $this->translations = $this->data_handler->read(self::TRANSLATION_ID);
        if (is_array($this->values["plays"]) && count($this->values["plays"]) > 0) {
            foreach ($this->values["plays"] as $i=>$play) {
                if (is_object($play)) {
                    $reflect = new \ReflectionClass($play);
                    if ($reflect->getShortName() === 'Play') {
                        $this->values["plays"][$i]->setLocale($locale, $use_existing_values);
                        continue;
                    }
                }
            }
        }
        $this->locale = $locale;
        return true;
    }
    
    /**
     * Returns current locale
     *
     * @return  string     Locale value
     */
    public function getLocale(): string
    {
        return $this->locale;
    }
    
    /**
     * Checks if drive is localized defined language.
     *
     * @param   string  $locale     Locale name to be checked, e.g. "en_US". Case matters
     * @param   string  $attrubute  (optional) Name of the attribute to be checked.
     *                              If is set, then method checks existance of
     *                              attribute, not just locale.
     * @return  bool                True if translation exists, False if not.
     */
    public function isTranslated($locale, string $attribute = null): bool
    {
        // If $this->translations property doesn't have set of values in $locale
        // then language setting is not loaded
        if (!array_key_exists($locale, $this->translations)) {
            return false;
        }
        
        if ($attribute !== null) {
            if (!$this->data_handler) {
                throw new Exception("Please set handler with Drive::setDataHandler() method to read translation data");
            }
            
            if (array_key_exists($attribute, $this->translations[$locale])) {
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
     * @param   Team    $possessing_team   Team object of the posessing team
     */
    public function setPossessingTeam(Team $possessing_team): void
    {
        $this->values["possessing_team"] = $possessing_team;
    }

    /**
     * Public method for setting defending team of the drive
     *
     * @param   Team    $defending_team   Team object of the defending team
     */
    public function setDefendingTeam(Team $defending_team): void
    {
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
     * Retrieves play from list of plays by its number
     *
     * @param   int    $i   (optional) Number of the play starting from 1.
     *                      If nothing is set, return last one
     * @return  Play        Object of Play
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
     * Retrieves full list of plays
     *
     * @return  array       Array of Play objects
     */
    public function getPlays(): array
    {
        return $this->values["plays"];
    }
    
    /**
     * Service function to return team and translate it if needed. Publicly used via
     * shortcuts getPossessingTeam() and getDefendingTeam()
     *
     * @param   string      $team       Kind of the team ("possessing_team" or "defending_team")
     * @return  string                  Team name or null
     */
    private function getTeam(string $team_type): ?Team {
        if (!(array_key_exists($team_type, $this->values))) {
            return null;
        }
        
        if ($this->isTranslated($this->locale)) {
            if (!$this->data_handler) {
                throw new Exception("Please set handler with Play::setDataHandler() method to read translation data");
            }
            $this->values[$team_type]->setDataHandler($this->data_handler);
            $this->values[$team_type]->setLocale($this->locale);
            return $this->values[$team_type];
        }
        
        return $this->values[$team_type];
    }
    
    public function getPossessingTeam(): ?Team { return $this->getTeam("possessing_team"); }
    public function getDefendingTeam(): ?Team { return $this->getTeam("defending_team"); }
    
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
            
            if ($this->isTranslated($this->locale, $this->values[$property])) {
                if (!$this->data_handler) {
                    throw new Exception("Please set handler with Drive::setDataHandler() method to read translation data");
                }
                return $this->translations[$this->locale][$this->values[$property]];
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
     * Import values from array provided by Drive::export();
     *
     * @param   array   $values   Import values
     * @return  void
     */
    public function import(array $values): void
    {
        foreach ($values as $name=>$value) {
            if ($value === null) {
                continue;
            }
            $method_name = Inflector::underscoreToCamelCase("set_" . $name);
            if (method_exists($this, $method_name)) {
                $this->{$method_name}($value);
            } else if (array_key_exists($name, $this->values)) {
                $this->values[$name] = $value;
            }
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
        $export = $this->values;
        if (is_array($export["plays"]) && count($export["plays"]) > 0) {
            foreach ($export["plays"] as $i=>$play) {
                if (is_a($play, '\Robin\Play')) {
                    $export["plays"][$i] = $play->export();
                    continue;
                }
                unset ($export["plays"][$i]);
            }
        }
        $export["language"] = $this->language;
        if (isset($this->locale)) {
            $export["locale"] = $this->locale;
        }
        return $export;
    }
}