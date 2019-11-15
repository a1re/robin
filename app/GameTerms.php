<?php
    
namespace Robin;

 /**
  * Abstract class with constants for Plays, Drives and game description
  * 
  * @package    Robin
  * @author     Yuriy Marin <yuriy.marin@gmail.com>
  */
abstract class GameTerms
{
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
    
    const TOUCHDOWN = "Touchdown";
    const FIELD_GOAL = "Field goal";
    const END_OF_HALF = "End of half";
    const DOWNS = "Lost on downs";
    const SAFETY = "Safety";
    const TURNOVER = "Turnover";
    const END_OF_GAME = "End of game";
    const PUNTED = "Punt";

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
    
    const OFFENSIVE_PLAY_TYPES = [
        self::RUN, self::PASS
    ];
    
    const ENDINGS = [
        self::TACKLE, self::SACK, self::FUMBLE, self::INTERCEPTION,
        self::PASS_DEFLECTION, self::PUNT_BLOCK, self::OTHER
    ];
    
    const DEFENSIVE_PLAY_TYPES = [
        self::INTERCEPTION_RETURN, self::FUMBLE_RETURN, self::FUMBLE_RECOVERY
    ];
    
    const SPECIAL_PLAY_TYPES = [
        self::KICK, self::PUNT, self::KICKOFF_RETURN, self::KICK_RETURN,
        self::PUNT_RETURN, self::PUNT_RECOVERY
    ];
    
    const SCORING_METHODS = [
        self::TD, self::FG, self::SF, self::XP, self::X2P, self::D2P
    ];
    
    const DRIVE_ENDINGS = [
        self::TOUCHDOWN, self::FIELD_GOAL, self::DOWNS, self::SAFETY,
        self::TURNOVER, self::PUNTED, self::END_OF_HALF, self::END_OF_GAME
    ];
}