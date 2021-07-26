<?php
    
namespace Robin;

use \Exception;
use \Robin\Logger;
use \Robin\Essence;

 /**
  * Class for Team entities that extends Essence
  * 
  * @package    Robin
  * @author     Yuriy Marin <yuriy.marin@gmail.com>
  */
class Conference extends Essence
{
    use Logger;

    protected static $default_language;
    private static $static_abbrs = [ "FBS" ];
    private static $postfixes = [ "Conference" ];

    /**
     * Class constructor. Instance of Conference can be created with the name of it
     * or by restoring the array from Team::export() method.
     *
     * @param   mixed  $title       Full name of a conference. If array is passed,
     *                              it is used to restore full object.
     */
    public function __construct($title, string $abbr = "")
    {
        // If class doesn't have its own default language set, we take it from parent class
        if (!self::$default_language) {
            self::$default_language = parent::$default_language;
        }
        
        parent::__construct("Conferences");
        $this->language = self::$default_language;
        
        $this->setAttributes(["title", "abbr"]);
        
        if (is_array($title) && count($title) > 0) {
            if (!$this->import($title)) {
                throw new Exception("Import from array failed");
            }
            $this->id = $this->title;
            return;
        }
        
        // If title is empty, the exception is thrown
        if (strlen(trim($title)) == 0) {
            throw new Exception("No title was provided");
        }
        
        if (strlen(trim($abbr)) == 0) {
            $abbr = self::makeAbbr($title);
        }
        
        $this->title = $title;
        $this->abbr = $abbr;
        $this->id = $this->title;
    }
    
    /**
     * Makes abbreviation out of a long title
     *
     * @param   string  $title  Full title
     *
     * @return  string
     */
    public static function makeAbbr(string $title): string
    {
        if (strlen($title) == 0) {
            throw new Exception("No title was provided");
        }
        
        $title_parts = preg_split("/\s|-/", $title);
        
        // If title consists of one word, we just take 3 or 4 first letters of it
        if (count($title_parts) == 1) {
            if (strlen($title_parts[0]) <= 4) {
                return mb_strtoupper($title_parts[0]);
            }
            
            return mb_strtoupper(mb_substr($title_parts[0], 0, 3));
        }
        
        $abbr = "";
        
        // Otherwise, shortening is composite of first letters
        for ($i = 0; $i < count($title_parts); $i++) {
            if (in_array($title_parts[$i], self::$static_abbrs)) {
                $abbr = $title_parts[$i];
                break;
            }

            if (is_numeric($title_parts[$i])) {
                $abbr .= $title_parts[$i];

                if (array_key_exists($i + 1, $title_parts) && in_array($title_parts[$i + 1], self::$postfixes)) {
                    break;
                }
            }

            $abbr .= mb_strcut($title_parts[$i], 0, 1);
        }
            
        return mb_strtoupper($abbr);
    }

}