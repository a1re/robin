<?php
    
namespace Robin\ESPN;

use \Exception;
use \Robin\Exceptions\ParsingException;
use \Robin\Logger;
use \Robin\ESPN\Team;
use \Robin\ESPN\Player;

 /**
  * Class for Events entities inside ESPN
  * 
  * @package    Robin
  * @subpackage ESPN
  * @author     Yuriy Marin <yuriy.marin@gmail.com>
  */
class Event
{
    use Logger;
    
    const TD = "TD";
    const FG = "FG";
    const SF = "SF";
    const XP = "XP";
    const X2P = "X2P";
    const D2P = "D2P";
    
    const RUN = "run";
    const RECEPTION = "pass from";
    const INTERCEPTION_RETURN = "interception return";
    const KICKOFF_RETURN = "kickoff return";
    const PUNT_RETURN = "punt return";
    const FUMBLE_RETURN = "fumble return";
    const FUMBLE_RECOVERY = "fumble recovery";
    const SAFETY = "safety";
    const KICK = "kick";
    const OTHER = "other";
    
    const Q1 = "Q1";
    const Q2 = "Q2";
    const Q3 = "Q3";
    const Q4 = "Q4";
    const OT = "OT";
    
    public $methods = [ self::TD, self::FG, self::SF, self::XP, self::X2P, self::D2P ];
    public $types = [ self::RUN, self::RECEPTION, self::INTERCEPTION_RETURN,
                      self::KICKOFF_RETURN, self::PUNT_RETURN, self::FUMBLE_RETURN,
                      self::FUMBLE_RECOVERY, self::SAFETY, self::KICK, self::OTHER ];

    public $origin = null;
    public $home_score;
    public $away_score;
    public $quarter;
    public $method;
    public $type;
    public $is_good = true;
    public $is_score;
    public $author;
    public $passer;
    public $team;
    
    /**
     * Class constructor
     *
     * @param   string  $type    Type of play, must be equal to one of $this->types. RUN is default
     * @param   string  $method  (optional) Method of score, must be equal to one of $this->mothods. TD is default
     */
    public function __construct(string $type = self::RUN, $method = null)
    {   
        if (in_array($type, $this->types)) {
            $this->type = $type;
        } else {
            throw new ParsingException("Unknown play type");
        }
        
        if ($method !== null) {
            $this->setScoringMethod($method);
        }
    }
    
    
    /**
     * Setter for $this->method. Sets scoring method and $this->is_score to true
     *
     * @param   string   $method   Method of score, must be equal to one of $this->mothods. TD is default
     */
    public function setScoringMethod(string $method = self::TD)
    {
        if (in_array($method, $this->methods)) {
            $this->method = $method;
            $this->is_score = true;
        } else {
            throw new ParsingException("Unknown scoring method");
        }
    }
    
    /**
     * Setters for $this->team, $this->author, $this->passer, $this->is_good,
     * $this->origin, $this->home_score, $this->away_score, $this->quarter.
     *
     * setTeam() accpets instance of Team as param
     * setAuthor() and setPasser() accepts instances of Player
     * setResult() accepts boolean value
     * setScore() accepts two ints, first — for home score, second — for away
     * setQuarter() accepts one of ScoringEvent::Q1, Q2, Q3, Q4, OT constants.
     */
    public function setTeam(Team $team): void { $this->team = $team; }
    public function setAuthor(Player $player): void { $this->author = $player; }
    public function setPasser(Player $player): void { $this->passer = $player; }
    public function setResult(bool $is_good): void { $this->is_good = $is_good; }
    public function setOrigin(string $origin): void { $this->origin = $origin; }
    public function setScore(int $home_score, int $away_score): void
    {
        $this->home_score = abs($home_score);
        $this->away_score = abs($away_score);
    }
    public function setQuarter(string $quarter = self::Q1): void
    {
        if (in_array($quarter, [ self::Q1, self::Q2, self::Q3, self::Q4, self::OT ])) {
            $this->quarter = $quarter;
        } else {
            throw new ParsingException("Unknown quarter");
        }
    }
    
    /**
     * Returns short name (string) of scored team or null
     */
    public function getTeamName(bool $is_full = false): ?string
    {
        if ($this->team == null) {
            return null;
        }
        
        if ($is_full == true) {
            return $this->team->full_name;
        }

        return $this->team->short_name;
    }
    
    /**
     * Returns abbr name (string) of scored team or null
     */
    public function getTeamAbbr(): ?string
    {
        if ($this->team !== null) {
            return $this->team->abbr;
        }
        
        return null;
    }
    
    /**
     * Returns full name (string) of points author or null
     */
    public function getAuthor(bool $include_position_and_number = false): ?string
    {
        if ($this->author !== null) {
            return $this->author->getFullName($include_position_and_number);
        }
        
        return null;
    }
    
    /**
     * Returns full name (string) of passer if it wass passing score or null
     */
    public function getPasser(bool $include_position_and_number = false): ?string
    {
        if ($this->passer !== null) {
            return $this->passer->getFullName($include_position_and_number);
        }
        
        return null;
    }
    
    /**
     * Returns boolean value of $this->is_good
     */
    public function isGood(): bool
    {
        return (bool) $this->is_good;
    }
    
}