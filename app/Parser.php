<?php

namespace Robin;

use \Exception;
use \Exceptions\ParsingException;
use \Robin\Logger;

 /**
  * Wrapper for parsing objects. Class constructor receives url to be parsed
  * as @param, parses via Simple HTML Dom function and selects proper class
  * to work with information. Every class has it's own methods.
  * 
  * @package    Robin
  * @author     Yuriy Marin <yuriy.marin@gmail.com>
  */
  
class Parser
{
    use Logger;
    
    public $engine_list = [ "\\ESPN\\Handler" => ["espn.com", "www.espn.com", "robin.local", "robin.firstandgoal.in"]];
    public $page;
    
    public function __construct(string $url)
    {
        require_once "simplehtmldom_1_9/simple_html_dom.php";
        
        // Checking if we have SimpleHTMLDOM loaded
        if (!function_exists("file_get_html")) {
            throw new ParsingException("Parsing function not defined");
        }
        
        // Checking if we creating object with a valid URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new ParsingException('Invalid URL');
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
        
        $url = $this->cache($url);
        
        $html = file_get_html($url);
        
        $this->page = new $engine($html);
    }
    
    /**
     * Cache function to avoid multiple requests to remote hosts. Once URL
     * is requested, method checks cache folder for saved version of HTML
     * and provides local URL if it's fresh (under 30 seconds)
     *
     * @param   string  $url    URL of parsed page
     *
     * @return  string          Cached file path or original URL if caching is
     *                          not avalible
     */
    private function cache(string $url): string
    {
        if (!defined("ROOT")) {
            $backtrace = debug_backtrace();
            $i = count($backtrace)-1;
            if (array_key_exists($i, $backtrace) && array_key_exists("file", $backtrace[$i])) {
                $dir = dirname($backtrace[$i]["file"]) . "/cache";
            } else {
                $dir = __DIR__ . "/cache";
            }
        } else {
            $dir = ROOT . "/cache";
        }
        
        if (!is_dir($dir)) {
            mkdir($dir, 0744);
        }
        
        $filename = $dir . "/" . md5($url);
        
        if (file_exists($filename)) {
            // If file exists, but  created more then 30 seconds ago, we delete it
            $created_time = filectime($filename);
            if($created_time && time() > $created_time+300) {
                unlink($filename);
            } else {
                return $filename;
            }
        }
        
        if (!file_exists($filename)) {
            $contents = file_get_contents($url);
            
            if (!$contents) {
                return $url;
            }
            
            $fp = fopen($filename, "w");
            
            if ($fp && flock($fp, LOCK_EX)) {
                fwrite($fp, $contents);
                flock($fp, LOCK_UN);
                fclose($fp);
                chmod($filename, 0744);
                
                $files = scandir($dir);
                
                // As long as cache creating is considered to be unoften operation,
                // we use it as a change for garbage collecting. We remove all
                // files from cache folder that were created more then 30 sec ago.
                foreach ($files as $existing_file) {
                    if (is_file($dir . "/" . $existing_file)) {
                        $created_time = filectime($dir . "/" . $existing_file);
                        if ($created_time && time() > $created_time+300) {
                            unlink($dir . "/" . $existing_file);
                        }
                    }
                }
                
                return $filename;
            }
        }
        return $url;
    }
    
    /**
     * Magic function to pass-through methods to parsed page handler 
     *
     * @return Object with ParsingEngine interface
     */
    public function __call(string $name, array $arguments)
    {
        return call_user_func_array([$this->page, $name], $arguments);
    }

}