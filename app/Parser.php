<?php

namespace Robin;

use \Exception;
use \Exceptions\ParsingException;
use \Robin\Logger;

class Parser
{
    use Logger;
    
    public $engine_list = [ "\\ESPN\\Handler" => ["espn.com", "www.espn.com", "robin.local", "robin.firstandgoal.in"]];
    public $page;
    
    public function __construct($url)
    {
        require_once "simplehtmldom_1_9/simple_html_dom.php";
        
        // Checking if we have SimpleHTMLDOM loaded
        if (!function_exists("file_get_html")) {
            throw new Exception("Parsing function not defined");
        }
        
        // Checking if we creating object with a valid URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid URL');
        }
        $domain = parse_url($url, PHP_URL_HOST);
        
        $engine = null;
        
        // Checking if URL belongs list of domains we can parse
        foreach ($this->engine_list as $engine_key => $supported_domains) {
            if (in_array($domain, $supported_domains)) {
                $engine = '\Robin'.$engine_key;
                break;
            }
        }

        // Checking if engine was found and class exists
        if (!$engine || !class_exists($engine)) {
            throw new Exception('No engine found for the domain');
        }
        
        $html = file_get_html($url);
        
        $this->page = new $engine($html);
    }

}