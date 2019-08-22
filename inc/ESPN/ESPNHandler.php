<?php
    
namespace Robin\ESPN;

use \Exception;
use \Robin\Logger;

class ESPNHandler
{
    use Logger;
    
    private $html;
    
    public $page_types = [ "Gamecast" => [ "engine" => "ESPNGamecast", "title" => "Game Summary"],
                           "Rankings" => [ "engine" => "ESPNRankings", "title" => "Polls and Rankings"],
                           "Standings" => [ "engine" => "ESPNStandings", "title" => "Standings"]];
                           
                               
    public function __construct($html)
    {
        if (!$html || !in_array(get_class($html), [ "simple_html_dom", "simple_html_dom_node"])) {
            throw new Exception("HTML DOM not received");
        }
        
        $this->html = $html;
        
        $engine = $this->getPageEngine($this->getPageType());
        
        if (!$engine || !class_exists($engine)) {
            throw new Exception('No engine found for the page');
        }
        
        $this->engine = new $engine($html);
    }
    
    private function getPageType()
    {
        $title = $this->html->find("head title", 0);
        
        if ($title) {
            foreach ($this->page_types as $type => $values) {
                if (mb_strpos($title->plaintext, $values["title"]) !== false) {
                    return $type;
                }
            }
        }
        
        return null;
    }
    
    private function getPageEngine($type)
    {
        if (isset($this->page_types[$type]["engine"])) {
            return "\\Robin\\ESPN\\".$this->page_types[$type]["engine"];
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