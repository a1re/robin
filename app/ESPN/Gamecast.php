<?php
    
namespace Robin\ESPN;

use \Exception;
use \Robin\Interfaces\ParsingEngine;

class Gamecast implements ParsingEngine
{
    protected $html;
    
    public function __construct($html)
    {
        if (!$html || !in_array(get_class($html), [ "simple_html_dom", "simple_html_dom_node"])) {
            throw new ParsingException("HTML DOM not received");
        }
        
        $this->html = $html;
    }
    
    public function getElement(string $name, $args = false)
    {
        return stdObject;
    }
    
    public function getMethods(): array
    {
        return [ ];
    }
}