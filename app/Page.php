<?php

namespace Robin;

use \Exception;
use \Robin\FileHandler;
use \Robin\Logger;

 /**
  * Wrapper for page parsing. Class constructor receives url to be parsed
  * as @param, parses via Simple HTML Dom function and selects proper class
  * to work with information. Every class has it's own methods.
  * 
  * @package    Robin
  * @author     Yuriy Marin <yuriy.marin@gmail.com>
  */
  
class Page
{
    use Logger;
    
    const ENGINES = [
        "ESPN/Gamecast" => [
            "class" => "\\Robin\\ESPN\\Gamecast",
            "pattern" => "#https?://(www\.)?espn\.com/(nfl|college-football)/game/_/gameId/([0-9]+)#i",
            "language" => "en_US"
        ]
    ];
    
    private $engine;
    
    /**
     * Class constructor
     *
     * @param    string  $url       URL of the page to be parsed
     * @param    string  $locale    (optional) Output locale
     */
    public function __construct(string $url, string $locale = "")
    {
        $engine_id = $this->getEngine($url);
        $engine_class = self::ENGINES[$engine_id]["class"];
        $language = self::ENGINES[$engine_id]["language"];
        $url = $this->cache($url);
        
        if (strlen($locale) == 0) {
            $locale = $language;
        }
        
        $this->engine = new $engine_class($url, $language, $locale);
    }
    
    /**
     * Getting the right class to work with passed URL by matching
     * the ENGINES patterns
     *
     * @param   string  $url    URL of the page to be parsed
     * @return  string          Engine id from Page::ENGINES
     */
    private static function getEngine(string $url): string
    {
        // Getting engine
        foreach (self::ENGINES as $engine_id => $values) {
            if(preg_match($values["pattern"], $url)) {
                return $engine_id;
            }
        }
        
        throw new Exception("Unsupported URL");
    }
    
    /**
     * Cache function to avoid multiple requests to remote hosts. Once URL
     * is requested, method checks cache folder for saved version of HTML
     * and provides local URL if it's fresh (under 30 seconds)
     *
     * @param   string  $url    URL of parsed page
     * @return  string          Cached file path or original URL if caching is
     *                          not avalible
     */
    private function cache(string $url): string
    {
        $fh = new FileHandler("cache");
        $filename = $fh->getFilePath(md5($url),true);
        
        if (file_exists($filename)) {
            // If file exists, but created more then 30 seconds ago, we delete it
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
                
                $files = scandir($fh->dir);
                
                // As long as cache creating is considered to be unoften operation,
                // we use it as a change for garbage collecting. We remove all
                // files from cache folder that were created more then 30 sec ago.
                foreach ($files as $existing_file) {
                    if (is_file($fh->dir . "/" . $existing_file)) {
                        $created_time = filectime($fh->dir . "/" . $existing_file);
                        if ($created_time && time() > $created_time+300) {
                            unlink($fh->dir . "/" . $existing_file);
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
        return call_user_func_array([$this->engine, $name], $arguments);
    }
}