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
    
    const Q1 = "Q1";
    const Q2 = "Q2";
    const Q3 = "Q3";
    const Q4 = "Q4";
    const OT = "OT";
    
    public $types = [ self::TD, self::FG, self::SF, self::XP, self::X2P, self::D2P ];
    public $methods = [ self::RUN, self::RECEPTION, self::INTERCEPTION_RETURN,
                        self::KICKOFF_RETURN, self::PUNT_RETURN, self::FUMBLE_RETURN,
                        self::FUMBLE_RECOVERY, self::SAFETY, self::KICK ];
    
    /**
     * Class constructor
     *
     * @param   string  $full_name      Full name of the team
     * @param   string  $short_name     (optional) Short name of the team
     * @param   string  $abbr           (optional) Abbreviation of the team
     */
    public function __construct(Team $team, string $type = self::TD)
    {
        
    }
    
    public function setMethod(string $method = self::RUSH)
    {
        
    }
    
    public function setScore(int $ht_score, int $at_score): void
    {
        
    }
    
    public function setAuthor(Player $player): void
    {
        
    }
    
    public function setPasser(Player $player): void
    {
        
    }
    
}