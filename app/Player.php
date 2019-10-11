<?php

namespace Robin;

use \Exception;
use \Robin\Exceptions\ParsingException;
use \Robin\Logger;
use \Robin\Essence;

 /**
  * Class for Players that extends Essence
  * 
  * @package    Robin
  * @author     Yuriy Marin <yuriy.marin@gmail.com>
  */
class Player extends Essence
{
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
        
        parent::__construct($language);
        $this->setAttributes(["first_name", "last_name", "first_name_genitive",
                              "last_name_genitive", "position", "number"]);
        
        $this->category = "Players";
        
        if (mb_strlen($l_name) === 0) {
            $this->splitFullName($f_name);
        } else {
            $this->first_name = $f_name;
            $this->last_name = $l_name;
        }
    }

    /**
     * Splits full name into first name and last name
     *
     * @param   string  $full_name     Full name, preferably with space inside
     */
    private function splitFullName(string $full_name): void
    {
        $doubleword_names = [ "Ha Ha" ];
        
        if(mb_strlen($full_name) === 0) {
            throw new Exception("No name was provided");
        }
        
        //Run through possible double names exceptions
        foreach ($doubleword_names as $double_name) {
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

    
    public function export()
    {
        return $this->values;
    }
    
}