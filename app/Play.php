<?php
    
namespace Robin;

use \Exception;
use \Robin\Exceptions\ParsingException;
use \Robin\Logger;
use \Robin\Inflector;
use \Robin\Keeper;
use \Robin\GameTerms;
use \Robin\Team;
use \Robin\Player;
use \Robin\Interfaces\Translatable;

 /**
  * Class for Plays entities
  * 
  * @package    Robin
  * @author     Yuriy Marin <yuriy.marin@gmail.com>
  */
class Play extends GameTerms implements Translatable
{
    use Logger;
    
    const TRANSLATION_ID = "gameterms"; // id for file with terms translation
    private static $default_language = "en_US";
    
    private $language;
    private $locale;
    private $translations = [];
    private $values = [
        "is_scoring_play" => false,
        "play_category" => null,
        "play_type" => null,
        "author" => null,
        "passer" => null,
        "defenders" => [],
        "ending" => null,
        "quarter" => null,
        "scoring_method" => null,
        "possessing_team" => null,
        "defending_team" => null,
        "gain" => null,
        "is_turnover" => false,
        "position_start" => null,
        "position_finish" => null,
        "origin" => null
    ];
    
    /**
     * Class constructor. Can be used in two different ways — by defining $play_type, $possessing_team
     * and $defending_team or by passing array of variables exported by method Play::export(), e.g.:
     * new Play($exported_values);
     *
     * @param   string  $play_type          Type of play, must be equal to one of preset play types from
     *                                      Play::$offensive_play_types, Play::$defensive_play_types
     *                                      and Play::$special_play_types
     * @param   Team    $possessing_team    Team (as object) that has posession on the beginning of the play
     * @param   Team    $defending_team     Team (as object) that acts as defening on the beginning of the play
     */
    public function __construct($play_type, $possessing_team = null, $defending_team = null)
    {
        $this->language = self::$default_language;
        $this->locale = self::$default_language;
        
        if (is_array($play_type)) {
            $import = $play_type;
            
            if (array_key_exists("language", $import)) {
                $this->language = $import["language"];
                unset($import["language"]);
            }
            
            if (array_key_exists("locale", $import)) {
                $this->locale = $import["locale"];
                unset($import["locale"]);
            }
            
            if (array_key_exists("translations", $import) && is_array($import["translations"])) {
                $this->translations = $import["translations"];
                unset($import["translations"]);
            }
            
            if (array_key_exists("play_type", $import)) {
                $play_type = $import["play_type"];
                unset($import["play_type"]);
            } else {            
                throw new Exception("Import array must contain play type value");
            }
            
            if (array_key_exists("possessing_team", $import) && is_array($import["possessing_team"])) {
                $possessing_team = new Team($import["possessing_team"]);
                unset($import["possessing_team"]);
            }
            
            if (array_key_exists("defending_team", $import) && is_array($import["defending_team"])) {
                $defending_team = new Team($import["defending_team"]);
                unset($import["defending_team"]);
            }   
        } elseif (!is_string($play_type)) {
            throw new Exception("Play type must be a valid non-empty string");
        }

        $play_type = trim($play_type);            
        if (strlen($play_type) == 0) {
            throw new Exception("Play type cannot be empty");
        }
            
        if (!is_a($possessing_team, '\Robin\Team')) {
            throw new Exception("Possessing team must be a valid Team object");
        }
            
        if (!is_a($defending_team, '\Robin\Team')) {
            throw new Exception("Defending team must be a valid Team object");
        }
            
        if (in_array($play_type, self::OFFENSIVE_PLAY_TYPES)) {
            $this->values["play_category"] = self::OFFENSIVE_PLAY;
            $this->values["play_type"] = $play_type;
        } else if (in_array($play_type, self::SPECIAL_PLAY_TYPES)) {
            $this->values["play_category"] = self::SPECIAL_PLAY;
            $this->values["play_type"] = $play_type;
        } else if (in_array($play_type, self::DEFENSIVE_PLAY_TYPES)) {
            $this->values["play_category"] = self::DEFENSIVE_PLAY;
            $this->values["play_type"] = $play_type;
        } else {
            throw new Exception("Unknown play type: \"" . $play_type . "\"");
        }
            
        $this->values["possessing_team"] = $possessing_team;
        $this->values["defending_team"] = $defending_team;
        
        if (isset($import)) {
            $this->import($import);
        }
    }
    
    /**
     * STATIC METHOD
     * Sets the default language for all future instances of Play.
     *
     * @param   string  $language   Default language, e.g. "en_US"
     *
     * @return  void         
     */
    public static function setDefaultLanguage(string $language): void
    {
        if (strlen(trim($language)) == 0) {
            throw new Exception("Default language of Play cannot be empty");
        }
        
        self::$default_language = $language;
    }
    
    /**
     * Sets active locale of play.
     *
     * @param   string  $locale         Output locale of the variables, e.g. "en_US"
     * @return  bool                    True if locale was set, false if not
     */
    public function setLocale(string $locale): bool
    {
        if (strlen(trim($locale)) == 0) {
            throw new Exception("Locale of Play cannot be empty");
        }
        $this->translations = $this->data_handler->read(self::TRANSLATION_ID);
        $this->locale = $locale;
        return true;
    }
    
    /**
     * Returns current locale of play.
     *
     * @return  string     Locale value
     */
    public function getLocale(): string
    {
        return $this->locale;
    }
    
    /**
     * Checks if play is localized defined language.
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
                throw new Exception("Please set handler with Play::setDataHandler() method to read translation data");
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
    }
    
    /**
     * Sets ending of the play
     *
     * @param   string  $play_type      Type of play, must be equal to one of preset
     *                                  play types in Robin\GameTerms::ENDINGS
     */
    public function setEnding(string $play_type): void
    {
        $play_type = trim($play_type);
        
        if (strlen($play_type) == 0) {
            throw new Exception("Play type cannot be empty");
        }
        
        if (in_array($play_type, self::ENDINGS)) {
            $this->values["ending"] = $play_type;
        } else {
            throw new Exception("Unknown play ending: \"" . $play_type . "\"");
        }
    }
    
    /**
     * Sets defender ending the offensive play
     *
     * @param   mixed  $defenders     Player object of the defender (if one) or 
     *                                array with Player objects names (if many)
     */    
    public function setDefenders($defenders): void
    {
        if (is_array($defenders) && count($defenders) > 0) {
            foreach ($defenders as $defender) {
                if (is_a($defender, '\Robin\Player') && !in_array($defender, $this->values["defenders"])) {
                    $this->values["defenders"][] = $defender;
                }
            }
        }
        
        if (is_a($defenders, '\Robin\Player')) {
            if (!in_array($defenders, $this->values["defenders"])) {
                $this->values["defenders"][] = $defenders;
            }
        }
    }
    
    /**
     * Sets ending of the play as interception
     *
     * @param   string  $defender   (optional) Name of the defender, who intercepted
     *                              the pass
     */    
    public function setInterception(string $defender = ""): void
    {
        $this->values["is_turnover"] = 1;
        $this->values["defenders"] = [ $defender ];
        $this->values["ending"] = self::INTERCEPTION;
    }
    
    /**
     * Sets play as a turnover
     *
     * @param   boolean     $defender   (optional) True if turnover, false if not
     */
    public function setTurnover(bool $is_turnover): void
    {
        $this->values["is_turnover"] = $is_turnover;
    }

    /**
     * Sets play as a scoring play
     *
     * @param   boolean     $defender   (optional) True if scoring, false if not
     */
    public function setResult(bool $is_scoring_play): void
    {
        $this->values["is_scoring_play"] = $is_scoring_play;
    }
    
    /**
     * Sets author of the play
     *
     * @param   Player     $author   Player object of the author
     */
    public function setAuthor(Player $author): void
    {
        $this->values["author"] = $author;
    }
    
    /**
     * Sets passer if it was an offensive passing play
     *
     * @param   Player     $passer   Player object of the passer
     */
    public function setPasser(Player $passer): void
    {
        $this->values["passer"] = $passer;
    }
    
    /**
     * Defines the original description of the play
     *
     * @param   string     $origin    Original description
     */
    public function setOrigin(string $origin): void
    {
        $this->values["origin"] = $origin;
    }
    
    /**
     * Sets method (TD, FG, etc.) of scoring play. Automatically calls Play::setResult(true)
     *
     * @param   string     $method   (optional) Method of scoring points, must be equal
     *                               to one of Play::$scoring_methods. Default is Play::TD
     */    
    public function setScoringMethod(string $method = self::TD): Void
    {
        if (!in_array($method, self::SCORING_METHODS)) {
            throw new Exception("Unknown scoring method \"" . $method . "\"");
        }
        
        $this->values["scoring_method"] = $method;
        $this->setResult(true);
    }
    
    /**
     * Sets yardage gain of the play
     *
     * @param   int    $gain   Number of yards gained in play. Can be negative
     */
    public function setGain(int $gain)
    {
        $this->values["gain"] = $gain;
    }
    
    /**
     * Sets starting point of the play
     *
     * @param   int    $start   Starting position, can be from 0 to 100
     */
    public function setPositionStart(int $start)
    {
        $this->values["position_start"] = $start;
    }
    
    /**
     * Sets ending point of the play
     *
     * @param   int    $start   Ending position, can be from 0 to 100
     */
    public function setPositionFinish(int $finish)
    {
        $this->values["position_finish"] = $finish;
    }
    
    /**
     * Sets ending point of the play
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
     * Service function to return player and translate name if needed. Publicly used via
     * shortcuts getAuthor() and getPasser()
     *
     * @param   string      $team       Kind of the player ("author" or "passer")
     * @return  string                  Player name or null
     */
    private function getPlayer(string $player_type): ?Player {
        if (!(array_key_exists($player_type, $this->values) && is_a($this->values[$player_type], "\Robin\Player"))) {
            return null;
        }
        
        if ($this->isTranslated($this->locale)) {
            if (!$this->data_handler) {
                throw new Exception("Please set handler with Play::setDataHandler() method to read translation data");
            }
            $this->values[$player_type]->setDataHandler($this->data_handler);
            $this->values[$player_type]->setLocale($this->locale);
            return $this->values[$player_type];
        }
        
        return $this->values[$player_type];       
    }
    
    public function getAuthor(): ?Player { return $this->getPlayer("author"); }
    public function getPasser(): ?Player { return $this->getPlayer("passer"); }

    /**
     * Return list of defending players of the play and translate their names if needed.
     *
     * @return  array                   List of defending players
     */    
    public function getDefenders(): array {
        if ($this->isTranslated($this->locale)) {
            if (!$this->data_handler) {
                throw new Exception("Please set handler with Play::setDataHandler() method to read translation data");
            }
            
            $defenders = [];
            
            foreach ($this->values["defenders"] as $defender) {
                $defender->setDataHandler($this->data_handler);
                $defender->setLocale($this->locale);
                
                $defenders[] = $defender;
            }
            
            return $defenders;
        }
        
        return $this->values["defenders"];       
    }
    
    /**
     * Magic method for to acceess private properties. Properties are accessed via
     * method with "get" prefix and property name in camelCase, e.g. $this->values["play_type"]
     * is access via method Play::getPlayType();. Properties with names starting with 
     * can be accessed withot get, e.g. Play::isTurnover() for $this->values["is_turnover"];
     */
    public function __call($name, $arguments)
    {
        if (mb_substr($name, 0, 3) == "get") {
            $property = Inflector::camelCaseToUnderscore(mb_substr($name, 3));
            
            if (!array_key_exists($property, $this->values)) {
                throw new Exception("Call to undefined method " . $name . "() in Robin\Play");
            }
            
            if ($this->isTranslated($this->locale, $this->values[$property])) {
                if (!$this->data_handler) {
                    throw new Exception("Please set handler with Play::setDataHandler() method to read translation data");
                }
                return $this->translations[$this->locale][$this->values[$property]];
            }
            
            return $this->values[$property];
        } else if (mb_substr($name, 0, 2) == "is") {
            $property = Inflector::camelCaseToUnderscore($name);
            
            if (!array_key_exists($property, $this->values)) {
                throw new Exception("Call to undefined method " . $name . "() in Robin\Play");
            }
            
            return $this->values[$property];
        } else {
            throw new Exception("Call to undefined method " . $name . "() in Robin\Play");
        }
    }
    
    /**
     * Magic method for public reading object properties from $this->values array.
     *
     * @return  mixed
     */
    public function __get(string $name)
    {
        if (!array_key_exists($name, $this->values)) {
            throw new Exception("Unknown property \"" . $name . "\"");
        }
        
        return $this->values[$name];
    }
    
    /**
     * Import values from array provided by Play::export();
     *
     * @param   array   $values   Import values
     * @return  void
     */
    public function import(array $values): void
    {
        if (array_key_exists("author", $values) && is_array($values["author"])) {
            $this->values["author"] = new Player($values["author"]);
            unset($values["author"]);
        }
        
        if (array_key_exists("passer", $values) && is_array($values["passer"])) {
            $this->values["passer"] = new Player($values["passer"]);
            unset($values["passer"]);
        }
        
        if (array_key_exists("defenders", $values) && is_array($values["defenders"])) {
            foreach ($values["defenders"] as $defender) {
                if (is_array($defender) && count($defender) > 0) {
                    $this->values["defenders"][] = new Player($defender);
                }
            }
            unset($values["defenders"]);
        }
        
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
        if ($export["author"] != null && is_a($export["author"], '\Robin\Player')) {
            $export["author"] = $export["author"]->export();
        }
        if ($export["passer"] != null && is_a($export["passer"], '\Robin\Player')) {
            $export["passer"] = $export["passer"]->export();
        }
        if (is_array($export["defenders"])) {
            for ($i=0;$i<count($export["defenders"]);$i++) {
                $export["defenders"][$i] = $export["defenders"]->export();
            }
        }
        $export["possessing_team"] = $this->values["possessing_team"]->export();
        $export["defending_team"] = $this->values["defending_team"]->export();
        $export["language"] = $this->language;
        if (isset($this->locale)) {
            $export["locale"] = $this->locale;
        }
        if (count($this->translations) > 0) {
            $export["translations"] = $this->translations;
        }
        return $export;
    }
}