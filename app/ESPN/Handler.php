<?php
    
namespace Robin\ESPN;

use \Exception;
use \Robin\Exceptions\ParsingException;
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
                              "College Football Rankings" => "Rankings",
                              "Standings" => "Standings" ];
                                   
    public function __construct($html)
    {
        if (!$html || !in_array(get_class($html), [ "simple_html_dom", "simple_html_dom_node"])) {
            throw new Exception("HTML DOM not received");
        }
        
        $this->html = $html;
        
        $this->engine = $this->getEngine();
    }
    
    /**
     * Gets an object for parsing page based on title and self::$pages_engines
     *
     * @return Object with ParsingEngine interface
     */
    private function getEngine(): ParsingEngine
    {
        $title = $this->html->find("head title", 0);
        
        $engine_name = $this->getType();
        
        if ($engine_name) {
            $engine = "\\Robin\\ESPN\\" . $engine_name;
            
            if (class_exists($engine)) {
                return new $engine($this->html);
            }
            
            throw new ParsingException("No engine found for page \"" . $engine ."\"");
        }
        throw new ParsingException("Undefined page");
    }
    
    /**
     * Parses header of the page and returns its type according to 
     *
     * @return string with page type or NULL
     */
    public function getType(): ?string
    {
        $title = $this->html->find("head title", 0);
        
        if ($title) {
            foreach ($this->pages_engines as $name => $engine) {
                if (mb_strpos($title->plaintext, $name) === false) {
                    continue;
                }
                
                return $engine;
            }
        }
        
        return null;        
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