<?php
    
namespace Robin\ESPN;

use \Exception;

class ESPNGamecast
{
    protected $html;
    
    public function __construct($html)
    {
        if (!$html || !in_array(get_class($html), [ "simple_html_dom", "simple_html_dom_node"])) {
            throw new Exception("HTML DOM not received");
        }
        
        $this->html = $html;
    }
}