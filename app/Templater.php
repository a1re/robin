<?php

namespace Robin;

use \Exception;
use \Robin\FileHandler;
use \Robin\Logger;

 /**
  * Tiny templater class. To use it, create new Templater object with
  * tempaltes diretory name as constructor value and then use method
  * make(string $template_name, array $values), where $template_name
  * is template file in directory without extension and $values is array
  * of appliable values. Template is simple php file with HTML and PHP code.
  * Values are php variables, e.g.:
  *        Templater::make('header', [ "name" => "John Doe" ]);
  *
  *        header.php example:
  *             <body>
  *                <p><?=$name ?></p>
  *            </body> 
  * 
  * @package    Robin
  * @author     Yuriy Marin <yuriy.marin@gmail.com>
  */
  
class Templater
{
    use Logger;
    
    private $dir;
    
    /**
     * Class constructor
     *
     * @param    string  $dir      Templates directory, relativly to project root
     */
    public function __construct(string $dir)
    {
        $root_dir = FileHandler::getRoot();
        
        if (strlen($dir) > 0) {
            // If user defined $dir is ended with "/", we cut it off to avoid
            // checking in further operations
            if (substr($dir, -1) == "/") {
                $dir = substr($dir, 0, -1);
            }
            
            // Merging $dir with $root_dir to make full path. If user defined $dir
            // with starting "/", we join it directly, otherwise add missing slash
            if (substr($dir, 0, 1) == "/") {
                $root_dir = $root_dir . $dir;
            } else {
                $root_dir = $root_dir . "/" . $dir;
            }
        }
        
        $this->dir = $root_dir;
    }
    
    /**
     * Sets header for output, e.g. Templater::setHeader("html");
     *
     * @param    string    $content_type    Short ("html", "json") or long definition
     *                                      of content type
     * @return   void
     */
    public function setHeader(string $content_type = ""): void
    {
        if (strlen($content_type) == 0) {
            $content_type = "text/html";
        }
        
        if ($content_type == "html") {
            $content_type = "text/html";
        }
        if ($content_type == "json") {
            $content_type = "application/json";
        }
        
        header("Content-Type: " . $content_type . ";charset=utf-8");
    }
    
    /**
     * Puts values into template and return page that could be sent to browser output
     *
     * @param    string    $template_name    Name of the template in defined directory
     *                                       without extension, e.g. 'header' for
     *                                       header.php
     * @param    array     $values           List of values to be applied into template
     * @return   string
     */
    public function make(string $template_name, array $values = []): string
    {
        $filename = $this->dir . "/" . $template_name . ".php";
        
        if (!is_file($filename)) {
            throw new Exception("Template \"" . $template_name ."\" is not found");
        }
        
        ob_start();
        extract($values);
        include $filename;
        $result = ob_get_contents();
        ob_end_clean();
        
        return $result;
    }
}