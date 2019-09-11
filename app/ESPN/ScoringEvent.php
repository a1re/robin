<?php
    
namespace Robin\ESPN;

use \Exception;
use \Robin\Exceptions\ParsingException;
use \Robin\Logger;
use \Robin\ESPN\Team;
use \Robin\ESPN\Player;

 /**
  * Class for Scoring Events entities inside ESPN
  * 
  * @package    Robin
  * @subpackage ESPN
  * @author     Yuriy Marin <yuriy.marin@gmail.com>
  */
class ScoringEvent
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
    
    public $types = [ self::TD, self::FG, self::SF, self::XP, self::X2P, self::D2P ];
    public $methods = [ self::RUN, self::RECEPTION, self::INTERCEPTION_RETURN,
                        self::KICKOFF_RETURN, self::PUNT_RETURN, self::FUMBLE_RETURN,
                        self::FUMBLE_RECOVERY, self::SAFETY, self::KICK, self::OTHER ];

    public $is_good = true;
    public $origin = null;
    public $method;
    public $home_score;
    public $away_score;
    public $type;
    
    public $author;
    public $passer;
    public $team;
    
    /**
     * Class constructor
     *
     * @param   string  $full_name      Full name of the team
     * @param   string  $short_name     (optional) Short name of the team
     * @param   string  $abbr           (optional) Abbreviation of the team
     */
    public function __construct(string $type = self::TD, string $method = self::RUSH)
    {
        if (in_array($type, $this->types)) {
            $this->type = $type;
        } else {
            throw new ParsingException("Unknown scoring type");
        }
        
        if (in_array($method, $this->methods)) {
            $this->method = $method;
        } else {
            throw new ParsingException("Unknown scoring method");
        }
    }
    
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
    
    public function getTeamName(): ?string
    {
        if ($this->team !== null) {
            return $this->team->short_name;
        }
        
        return null;
    }
    
    public function getTeamAbbr(): ?string
    {
        if ($this->team !== null) {
            return $this->team->abbr;
        }
        
        return null;
    }
    
    public function getAuthor(bool $include_position_and_number = false): ?string
    {
        if ($this->author !== null) {
            return $this->author->getFullName($include_position_and_number);
        }
        
        return null;
    }
    
    public function getPasser(bool $include_position_and_number = false): ?string
    {
        if ($this->passer !== null) {
            return $this->passer->getFullName($include_position_and_number);
        }
        
        return null;
    }
    
}