<?php
/*
 * Trait Logger to enable logging method for any object. If there is a global
 * variable $logger of Monolog\logger, it is used to log, otherwise, it just
 * collects messages in public $logger array;
 *
 * To add logging method add `use Logger` in class description and then use
 * $this->log(message) method.
 *
 */

namespace Robin;

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
                $first_translation = current($this->translations);
                if (is_array($first_translation)) {
                    foreach($first_translation as $value) {
                        $id[] = $value;
                    }
                }
            }
            
            $id = implode(" ", $id);
        }
        
        if (mb_strlen($id) > 0) {
            return $id;
        }

        if (count($attrs) > 0) {
            $id = [ ];
            foreach($attrs as $value) {
                if (isset($this->{$value})) {
                    $id[] = $this->{$value};
                }
            }
            $id = implode(" ", $id);
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
     * @param   string  $value     (optional) should be set if previous param is not array.
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
    
    public function applyTranslation(string $language = ""): bool
    {
        if (mb_strlen($language) == 0 && is_array($this->translations)) {
            reset($this->translations);
            $language = key($this->translations);
        }
        
        if (mb_strlen($language) == 0 || !array_key_exists($language, $this->translations)) {
            return false;
        }
        
        if (is_array($this->translations[$language])) {
            foreach ($this->translations[$language] as $key => $value) {
                $this->{$key} = $value;
            }
            return true;
        }
        
        return false;
    }
    
    public function saveTranslation(string $folder = ""): void
    {
        $backtrace = debug_backtrace();
        $dir = dirname($backtrace[0]["file"]) . "/translations";
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755);
        }
        
        if(mb_strlen($folder) > 0) {
            if (strpos($folder, "...") !== false) {
                throw new ParsingException("Incorrect folder name for translation saving");
            }
            
            if (!preg_match("/^[a-z0-9\-\._ ]{1,32}$/i", $folder)) {
                throw new ParsingException("Incorrect folder name for translation saving");
            }
            
            $dir .= "/" . $folder;
            if (!is_dir($dir)) {
                mkdir($dir, 0755);
            }
        }
    }
}