<?php
namespace Robin;

use \Exception;
use \Robin\Exceptions\ParsingException;

trait Translate
{
    public $translations = [ ];

    /**
     * Return id of the translation. Method forms it in different ways — at first,
     * it searches for existing original values and puts into one string.
     * If no values are set, method searches for the first avalible translation.
     * If translation is also not found, method returns random hash of 10 chars.
     * It's highly recommeneded to overwrite this method in class.
     *
     * @return  string  Translation id.
     */    
    public function getId()
    {
        $attrs = $this->getAttributes();
        $id = "";
        
        if (count($attrs) > 0) {
            $id = [ ];
            
            foreach($attrs as $value) {
                if (isset($this->{$value})) {
                    $id[] = $this->{$value};
                }
            }
            
            if (!count($id) && is_array($this->translations) && count($this->translations) > 0) {
                $translation = current($this->translations);
                if (is_array($translation)) {
                    foreach($translation as $value) {
                        $id[] = $value;
                    }
                }
            }
            
            $id = implode(" ", $id);
        }
        
        if (mb_strlen($id) > 0) {
            return $id;
        }
        
        return substr(str_shuffle(MD5(microtime())), 0, 10);
    }

    /**
     * Set translation attributes. Can accept values in different ways — as array
     * or keys and values separately, e.g. self::setTranslation("en", ["name"=>"John"])
     * or self::setTranslation("en", "name", "John"). Method with array can accept
     * multiple attributes but the array cannot contain any subarrays and keys are
     * mandatory string.
     *
     * @param   string  $language   Original language of the name variables, e.g. "en"
     * @param   mixed   $attributes String with value name or associative array.
     * @param   string  $value      (optional) should be set if previous param is not array.
     */
    public function setTranslation(string $language, $mixed_attributes, string $value = ""): void
    {
        if (mb_strlen($language) == 0) {
            throw new ParsingException("Empty language value");
        }
        
        // self::setTranslation can receive translation in form of array, but
        // keys can be only string due to the fact that they will be applied to
        // object as values, e.g array("name"=>"John") to $player->name = "John"
        if (is_array($mixed_attributes)) {
            foreach ($mixed_attributes as $k => $v) {
                $k = str_replace(["=",";"], "", $k);
                if (is_string($k) && (is_string($v) || is_numeric($v))) {
                    $this->translations[$language][$k] = $v;
                }
            }
        } else if(is_string($mixed_attributes) && mb_strlen($value) > 0) {
            $this->translations[$language][$mixed_attributes] = $value;
        }
    }

    /**
     * Return list of attributes. This is just a safe option in case class doesn't
     * have its own getAttributes — in most cases it's better for object to have its
     * own method.
     *
     * @return  array   List of Attribute names.
     */    
    public function getAttributes(): array
    {
        if (is_array($this->translations) && count($this->translations)) {
            $values = current($this->translations);
            if (is_array($values)) {
                return array_keys($values);
            } else {
                return [ ];
            }
        } else {
            return [ ];
        }
    }
    
    /**
     * Get original language of the object.
     *
     * @return  string   Original language of the objetc or empty string.
     */ 
    public function getOriginalLanguage(): string
    {
        if (isset($this->language)) {
            return $this->language;
        }
        
        return "";
    }
    
    /**
     * Return certain attribute translation.
     *
     * @return  string   Translation value or null
     */ 
    public function getTranslation(string $language, string $attrubute): ?string
    {
        if (is_array($this->translations) && array_key_exists($language, $this->translations)) {
            if (is_array($this->translations[$language]) && array_key_exists($attrubute, $this->translations[$language])) {
                return $this->translations[$language][$attrubute];
            }
        }
        
        return null;
    }
    
    /**
     * Return all attributes translations.
     *
     * @return  array   Array with attributes and translations or empty array
     */ 
    public function getTranslationsList(string $language): array
    {
        if (is_array($this->translations) && array_key_exists($language, $this->translations)) {
            return $this->translations[$language];
        }
        
        return [ ];
    }
    
    /**
     * Collects original attributes via self::getAttributes to translations
     * array with original language
     */ 
    public function composeOriginal(): void
    {
        $attrubutes = $this->getAttributes();
        $original_language = $this->getOriginalLanguage();
        
        if (mb_strlen($original_language) == 0) {
            return;
        }
        
        foreach($attrubutes as $attrubute) {
            if (isset($this->{$attrubute})) {
                $this->setTranslation($original_language, $attrubute, $this->{$attrubute});
            }
        }
    }
    
    /**
     * Replaces primary object values with translation
     *
     * @param   string  $language       language of the translation to be applied
     * @param   bool    $save_original  If true, then self::composeOriginal will be called first
     *
     * @return  bool                    True if translation was found and applied, false if not;
     */ 
    public function applyTranslation(string $language = "", bool $save_original = true): bool
    {        
        if (mb_strlen($language) == 0 && is_array($this->translations)) {
            reset($this->translations);
            $language = key($this->translations);
        }
        
        if (mb_strlen($language) == 0 || !array_key_exists($language, $this->translations)) {
            return false;
        }
        
        if (is_array($this->translations[$language])) {
            
            // If translation was found and $save_original set to true, we call
            // self::composeOriginal to save original values
            if ($save_original == true) {
                $this->composeOriginal();
            }
        
            foreach ($this->translations[$language] as $key => $value) {
                $this->{$key} = $value;
            }
            $this->language = $language;
            return true;
        }
        
        return false;
    }
    
    /**
     * Save translation to ini file. I uses folder translations in the root dir.
     * Translations optionally could be stored inside folders. Filename of the
     * translation is formed from self::getId(), where spaces replaced with
     * underscore and "ini" extension is added.
     *
     * @param   string  $folder     Folder to save translation file.
     *
     * @return  bool                True if translation was found and applied, false if not;
     */
    public function saveTranslation(string $folder = ""): bool
    {
        $filename = $this->getTranslationFileneme($folder, true);
        
        $ini = "";
        
        // Composing ini source
        foreach ($this->translations as $language=>$values) {
            $ini .= "[" . $language . "]" . PHP_EOL;
            
            if (is_array($values)) {
                foreach ($values as $attrubute => $translation) {
                    $translation = str_replace(PHP_EOL, " ", $translation);
                    $translation = addslashes($translation);
                    $ini .= $attrubute . " = \"" . $translation . "\";" . PHP_EOL;
                } 
            }
            
            $ini .= PHP_EOL;
        }
        
        $fp = fopen($filename, "w");
        
        if ($fp && flock($fp, LOCK_EX)) {
            fwrite($fp, $ini);
            flock($fp, LOCK_UN);
            fclose($fp);
            chmod($filename, 0744);
            
            return true;
        }
        
        // If writing to file was not successfull, we return false;        
        return false;
    }

    /**
     * Reads translation from ini file. If folder is specified, then it uses it
     * as a part of path to filename. Filename is based on self::getId(), where
     * spaces replaced with underscore and "ini" extension is added. All languages
     * found are put to $this->translations. If specified language is found, method
     * returns true.
     *
     * @param   string  $folder     Folder of the translation file.
     *
     * @return  bool                True if translation was found, false if not;
     */
    public function readTranslation(string $language, string $folder = ""): bool
    {
        $filename = $this->getTranslationFileneme($folder, false);
        
        if (file_exists($filename) && is_file($filename)) {
            $translations = parse_ini_file($filename, true);
            $language_exists = false;
            foreach ($translations as $language_from_ini => $attrubutes_from_ini) {
                if (is_array($attrubutes_from_ini)) {
                    if ($language == $language_from_ini) {
                        $language_exists = true;
                    }
                    
                    $this->setTranslation($language_from_ini, $attrubutes_from_ini);
                }
            }
            
            return $language_exists;
        }
        
        return false;
    }
    
    /**
     * Returns filename of the translation to be read ro write to with self::saveTranslation()
     * of self::readTranslation(). 
     *
     * @param   string  $folder         (optional) Folder of the translation file
     *                                             if deeper categorisation is needed
     * @param   string  $create_folder  (optional) Create $folder if it doesn't exist
     *                                             (appliable if folder name is correct
     *
     * @return  string                  Filename
     */
    private function getTranslationFileneme(string $folder = "", bool $create_folder = false): ?string
    {
        // Getting the root dir
        if (!defined("ROOT")) {            
            $backtrace = debug_backtrace();
            $i = count($backtrace)-1;
            if (array_key_exists($i, $backtrace) && array_key_exists("file", $backtrace[$i])) {
                $dir = dirname($backtrace[$i]["file"]) . "/i18n";
            } else {
                $dir = __DIR__ . "/i18n";
            }
        } else {
            $dir = ROOT . "/i18n";
        }
        
        if (!is_dir($dir)) {
            mkdir($dir, 0744);
        }

        // If folder variable is set, checking it for safety, replace spaces
        // with underline and create it if it doesn't exist.        
        if(mb_strlen($folder) > 0) {
            $folder = trim($folder);
            if (strpos($folder, "...") !== false) {
                throw new ParsingException("Incorrect folder name for translation saving for \"" . $folder ."\"");
            }
            
            if (!preg_match("/^[a-z0-9&\-\._ ]{1,32}$/i", $folder)) {
                throw new ParsingException("Incorrect folder name for translation saving for \"" . $folder ."\"");
            }
            
            $folder = preg_replace("/\s+/u", "_", $folder);
            
            $dir .= "/" . $folder;
            if (!is_dir($dir) && $create_folder == true) {
                mkdir($dir, 0744);
            }
        }
        
        // Gettin id and use it as filename with replacing spaces with underscore
        $id = trim(preg_replace("/[^a-zа-я0-9 ]/ui", "", $this->getId()));
        $filename = $dir . "/" . preg_replace("/\s+/u", "_", $id) . ".ini";
        
        return $filename;
    }
}