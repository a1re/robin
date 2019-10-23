<?php
    
namespace Robin;

use \Exception;
use \Robin\Exceptions\ParsingException;
use \Robin\Logger;
use \Robin\Essence;

 /**
  * Class for Plays entities
  * 
  * @package    Robin
  * @author     Yuriy Marin <yuriy.marin@gmail.com>
  */
class Play
{
    use Logger;

    // Offensive plays
    const OFFENSIVE_PLAY = "offensive";    
    const RUN = "run";
    const PASS = "pass from";
    
    // Endings
    const TACKLE = "tackled by";
    const SACK = "sacked by";
    const FUMBLE = "fumble";
    const LATERAL = "lateral pass";
    const INTERCEPTION = "intercepted by";
    const PASS_DEFLECTION = "deflected by";
    const PUNT_BLOCK = "punt block";
    const OTHER = "other";
    
    // Special plays
    const SPECIAL_PLAY = "special";   
    const KICK = "kick";
    const PUNT = "punt";
    const KICKOFF_RETURN = "kickoff return";
    const KICK_RETURN = "kick return";
    const PUNT_RETURN = "punt return";
    const PUNT_RECOVERY = "punt recovery";

    // Defensive plays
    const DEFENSIVE_PLAY = "defensive";
    const INTERCEPTION_RETURN = "interception return";
    const FUMBLE_RETURN = "fumble return";
    const FUMBLE_RECOVERY = "fumble recovery";

    // Scoring methods
    const TD = "TD";
    const FG = "FG";
    const SF = "SF";
    const XP = "XP";
    const X2P = "X2P";
    const D2P = "D2P";
    
    // Quarters
    const Q1 = "Q1";
    const Q2 = "Q2";
    const Q3 = "Q3";
    const Q4 = "Q4";
    const OT = "OT";
    
    public static $offensive_play_types = [
        self::RUN, self::PASS
    ];
    
    public static $endings = [
        self::TACKLE, self::SACK, self::FUMBLE, self::INTERCEPTION,
        self::PASS_DEFLECTION, self::PUNT_BLOCK, self::OTHER
    ];
    
    public static $defensive_play_types = [
        self::INTERCEPTION_RETURN, self::FUMBLE_RETURN, self::FUMBLE_RECOVERY
    ];
    
    public static $special_play_types = [
        self::KICK, self::PUNT, self::KICKOFF_RETURN, self::KICK_RETURN,
        self::PUNT_RETURN, self::PUNT_RECOVERY
    ];
    
    public static $scoring_methods = [
        self::TD, self::FG, self::SF, self::XP, self::X2P, self::D2P
    ];

    private $is_scoring_play = 0;
    private $play_category;
    private $play_type;
    private $author = null;
    private $passer = null;
    private $defenders = [];
    private $ending = null;
    private $quarter = null;
    private $scoring_method = null;
    private $possessing_team;
    private $defending_team;
    private $gain = null;
    private $is_turnover = 0;
    private $position_start = null;
    private $position_finish = null;
    private $origin = null;
    
    /**
     * Class constructor
     *
     * @param   string  $play_type          Type of play, must be equal to one of preset play types from
     *                                      Play::$offensive_play_types, Play::$defensive_play_types
     *                                      and Play::$special_play_types
     * @param   string  $possessing_team    Id of the team that has posession on the beginning of the play
     * @param   string  $defending_team     Id of the team that acts as defening on the beginning of the play
     */
    public function __construct(string $play_type, string $possessing_team, string $defending_team)
    {
        $play_type = trim($play_type);
        $possessing_team = trim($possessing_team);
        $defending_team = trim($defending_team);
        
        if (mb_strlen($play_type) == 0) {
            throw new Exception("Play type cannot be empty");
        }
        
        if (mb_strlen($possessing_team) == 0) {
            throw new Exception("Possessing team cannot be empty");
        }
        
        if (mb_strlen($defending_team) == 0) {
            throw new Exception("Defending team cannot be empty");
        }
        
        if (in_array($play_type, self::$offensive_play_types)) {
            $this->play_category = self::OFFENSIVE_PLAY;
            $this->play_type = $play_type;
        } else if (in_array($play_type, self::$special_play_types)) {
            $this->play_category = self::SPECIAL_PLAY;
            $this->play_type = $play_type;
        } else if (in_array($play_type, self::$defensive_play_types)) {
            $this->play_category = self::DEFENSIVE_PLAY;
            $this->play_type = $play_type;
        } else {
            throw new Exception("Unknown play type: \"" . $play_type . "\"");
        }
        
        $this->possessing_team = $possessing_team;
        $this->defending_team = $defending_team;
    }
    
    /**
     * Sets ending of the play
     *
     * @param   string  $play_type          Type of play, must be equal to one of preset play types from
     *                                      Play::$endings
     */
    public function setEnding(string $play_type): void
    {
        $play_type = trim($play_type);
        
        if (mb_strlen($play_type) == 0) {
            throw new Exception("Play type cannot be empty");
        }
        
        if (in_array($play_type, self::$endings)) {
            $this->ending = $play_type;
        } else {
            throw new Exception("Unknown play ending: \"" . $play_type . "\"");
        }
    }
    
    /**
     * Sets defender ending the offensive play
     *
     * @param   mixed  $defenders     Name of the defender (if one) or array with
     *                                names (if mamny)
     */    
    public function setDefenders($defenders): void
    {
        if (is_array($defender) && count($defenders) > 0) {
            foreach ($defenders as $defender) {
                if (is_string($defender) && !in_array($defender, $this->defenders)) {
                    $this->defenders[] = $defender;
                }
            }
        }
        
        if (is_string($defenders)) {
            if (!in_array($defender, $this->defenders)) {
                $this->defenders[] = $defender;
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
        $this->is_turnover = 1;
        $this->defenders = [$defender];
        $this->ending = self::INTERCEPTION;
    }
    
    /**
     * Sets play as a turnover
     *
     * @param   boolean     $defender   (optional) True if turnover, false if not
     */
    public function setTurnover(bool $is_turnover): void
    {
        $this->is_turnover = $is_turnover;
    }

    /**
     * Sets play as a scoring play
     *
     * @param   boolean     $defender   (optional) True if scoring, false if not
     */
    public function setResult(bool $is_scoring_play): void
    {
        $this->is_scoring_play = $is_scoring_play;
    }
    
    /**
     * Sets author of the play
     *
     * @param   string     $author   Id (Name) of the player
     */
    public function setAuthor(string $author): void
    {
        $author = trim($author);
        if (strlen($author) == 0) {
            throw new Exception("Author cannot be empty");
        }
        
        $this->author = $author;
    }
    
    /**
     * Sets passer if it was an offensive passing play
     *
     * @param   string     $passer   Id (Name) of the player
     */
    public function setPasser(string $passer): void
    {
        $passer = trim($passer);
        if (strlen($passer) == 0) {
            throw new Exception("Passer cannot be empty");
        }
        
        $this->passer = $passer;
    }
    
    /**
     * Defines the original description of the play
     *
     * @param   string     $origin    Original description
     */
    public function setOrigin(string $origin): void
    {
        $this->origin = $origin;
    }
    
    /**
     * Sets method (TD, FG, etc.) of scoring play. Automatically calls Play::setResult(true)
     *
     * @param   string     $method   (optional) Method of scoring points, must be equal
     *                               to one of Play::$scoring_methods. Default is Play::TD
     */    
    public function setScoringMethod(string $method = self::TD): Void
    {
        if (!in_array($method, self::$scoring_methods)) {
            throw new Exception("Unknown scoring method \"" . $method . "\"");
        }
        
        $this->scoring_method = $method;
        $this->setResult(true);
    }
    
    /**
     * Sets yardage gain of the play
     *
     * @param   int    $gain   Number of yards gained in play. Can be negative
     */
    public function setGain(int $gain)
    {
        $this->gain = $gain;
    }
    
    /**
     * Sets starting point of the play
     *
     * @param   int    $start   Starting position, can be from 0 to 100
     */
    public function setStart(int $start)
    {
        $this->position_start = $start;
    }
    
    /**
     * Sets ending point of the play
     *
     * @param   int    $start   Ending position, can be from 0 to 100
     */
    public function setFinish(int $finish)
    {
        $this->position_finish = $finish;
    }
    
    /**
     * Sets ending point of the play
     *
     * @param   int    $start   Ending position, can be from 0 to 100
     */
    public function setQuarter(string $quarter)
    {
        if (!in_array($quarter, [self::Q1, self::Q2, self::Q3, self::Q4, self::OT])) {
            throw new Exception("Incorrect qauarter \"" . $quarter . "\"");            
        }
        
        $this->quarter = $quarter;
    }
    
    /**
     * Magic nethod for reading private properties
     */    
    public function __get(string $name)
    {
        if (!property_exists($this, $name)) {
            throw new Exception("Unknown property \"" . $name . "\"");
        }
        
        return $this->$name;
    }

}