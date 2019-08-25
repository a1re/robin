<?php
    
namespace Robin\ESPN;

use \Exception;
use \Exceptions\ParsingException;
use \Robin\Logger;
use \Robin\Interfaces\ParsingEngine;

 /**
  * Handler class for parsing ESPN page of different types. Each page type has
  * its own class for parsing and subclasses for objects
  * 
  * @package    Robin
  * @subpackage ESPN
  * @author     Yuriy Marin <yuriy.marin@gmail.com>
  */
class Handler
{
    use Logger; // Trait with logging object, so $this->log(message) is available everywhere
    
    private $html; // variable for SimpleHTMLDom object
    
    // Array for pages and classes names for parsing
    public $pages_engines = [ "Game Summary" => "Gamecast",
                              "Polls and Rankings" => "Rankings",
                              "Standings" => "Standings" ];
                                   
    public function __construct($html)
    {
        if (!$html || !in_array(get_class($html), [ "simple_html_dom", "simple_html_dom_node"])) {
            throw new Exception("HTML DOM not received");
        }
        
        $this->html = $html;
        
        $this->engine = $this->getPageEngine();
    }
    
     /**
      * Gets an object for parsing page based on title and self::$pages_engines
      *
      * @return Object
      */
    private function getPageEngine(): ParsingEngine
    {
        $title = $this->html->find("head title", 0);
        
        if ($title) {
            foreach ($this->pages_engines as $name => $engine) {
                if (mb_strpos($title->plaintext, $name) === false) {
                    continue;
                }
                $engine_name = "\\Robin\\ESPN\\" . $engine;
                
                if (class_exists($engine_name)) {
                    return new $engine_name($this->html);
                }
            }
            throw new ParsingException("No engine found for page \"" . $title->plaintext ."\"");
        }
        throw new ParsingException("Undefined page");
    }
    
     /**
      * Gets an object for parsing page based on title and self::$pages_engines
      *
      * @return string with page type or FAL
      */
    public function getPageType(): string
    {
        $title = $this->html->find("head title", 0);
        
    }
    
    public function __get($name)
    {
        if (!is_numeric($name)) {
            $capitalized_string = ucwords(str_replace("_", " ", $name));
            $name = implode("", explode(" ", $capitalized_string));
        }
        
        $method = "get" . $name;
        
        echo $method;
        
        /*
        if (method_exists($this->engine, $method)) {
            return $this->engine->$method;
        }
        */
        
        return null;
    }
    
}