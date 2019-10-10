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
    private $type;
    private $source_language = "en";
    private $dictionary = [ ];
    private $language;
    
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
        $this->language = $this->source_language;
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
    
    public function setLanguage(string $language): void
    {
        $this->language = $language;
        $this->engine->language = $language;
        // translation dictionary is stored in .ini file with name of translation
        // direction of two languages divided by hyphen, where first language
        // is source and second is target, e.g. en_ru => english to russian.
        $filename = $this->source_language . "-" . $this->language . ".ini";
        
        // Getting the root dir
        if (!defined("ROOT")) {
            $backtrace = debug_backtrace();
            $i = count($backtrace)-1;
            if (array_key_exists($i, $backtrace) && array_key_exists("file", $backtrace[$i])) {
                $dir = dirname($backtrace[$i]["file"]) . "/i18n/" . $this->getType();
            } else {
                $dir = __DIR__ . "/i18n/" . $this->getType();
            }
        } else {
            $dir = ROOT . "/i18n/" . $this->getType();
        }
        
        if (file_exists($dir . "/" . $filename)) {
            $dictionary = parse_ini_file($dir . "/" . $filename, true);
        
            if (is_array($dictionary)) {
                $this->dictionary = $dictionary;
            }
        }
    }
    
    public function i18n(string $str, $pluralize_number = null): string
    {
        if ($this->language == $this->source_language) {
            return $str;
        }
        
        if ($pluralize_number === null) {
            if (array_key_exists($str, $this->dictionary) && !is_array($this->dictionary[$str])) {
                return $this->dictionary[$str];
            } else {
                return $str;
            }
        }
        
        if (array_key_exists($str, $this->dictionary) && is_array($this->dictionary[$str])) {
            foreach ($this->dictionary[$str] as $postfix => $value) {
                if (mb_substr($pluralize_number, (-1)*mb_strlen($postfix)) == $postfix) {
                    return $value;
                }
            }
        }
        
        return $str;
    }
    
    /**
     * Magic function to pass-through methods to parsed page egnine if they
     * are listed as public. Otherwise, function genereates exception
     */
    public function __call(string $name, array $arguments)
    {
        if (!in_array($name, $this->engine->getMethods())) {
            throw new ParsingException("No public method \"" . $name . "\" in page engine");
        }
        
        return call_user_func_array([$this->engine, $name], $arguments);
    }
    
}