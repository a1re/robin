<?php
    
namespace Robin\ESPN;

use \Exception;
use \Robin\Exceptions\ParsingException;
use \Robin\Logger;
use \Robin\Interfaces\ParsingEngine;

 /**
  * Class for Player entities inside ESPN
  * 
  * @package    Robin
  * @subpackage ESPN
  * @author     Yuriy Marin <yuriy.marin@gmail.com>
  */
class Player
{
    public $first_name = null;
    public $last_name = null;
    public $stats = [ ];
    
    private $doubleword_names = [ "Ha Ha" ];
    
    public function __construct(string $f_name, $l_name = null)
    {
        if (mb_strlen($l_name) === 0) {
            $this->splitFullName($f_name);
        } else {
            $this->first_name = $f_name;
            $this->last_name = $l_name;
        }
    }
    
    private function splitFullName(string $full_name): void
    {
        if(mb_strlen($full_name) === 0) {
            throw new Exception("No name was provided");
        }
        
        //Run through possible double names exceptions
        foreach ($this->doubleword_names as $double_name) {
            if (mb_strpos($full_name, $double_name) === 0) {
                $this->first_name = $double_name;
                $this->last_name = mb_substr($full_name, mb_strlen($double_name));
                return;
            }
        }
        
        $name_parts = explode(" ", $full_name);
        
        if(count($name_parts) < 2) {
            $this->first_name = "";
            $this->last_name = $name_parts[0];
            return;
        }
        
        $this->first_name = array_shift($name_parts);
        $this->last_name = join(" ", $name_parts);
    }
}