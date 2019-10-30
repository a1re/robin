<?php

namespace Robin;

use \Exception;
use \Robin\Exceptions\ParsingException;
use \Robin\Logger;
use \Robin\Inflector;

 /**
  * Essence class for basing on it different objects like Players, Teams, etc.
  * 
  * @package    Robin
  * @author     Yuriy Marin <yuriy.marin@gmail.com>
  */
class Essence
{
    use Logger;
    
    private $id;
    private $attributes = [];
    private $language;
    
    protected $values;
    protected $category = "Essences";
    
    const DIR = "data";
    
    /**
     * Class constructor.
     *
     * @param   string  $language   Current language of the variables, e.g. "en"
     */
    public function __construct(string $language)
    {
        if (mb_strlen($language) == 0) {
            throw new Exception("Empty language set for " . $this->category ." instance");
        }
        
        $this->setLanguage($language);
    }
    
    /**
     * Set list of attributes of the essence. Accepts array in two options — assoc
     * array with key as attribute name and value as attribute label (e.g.
     * ["first_name"=>"First name"]), and simple array with list of names (e.g.
     * ["first_name", "last_name"]). It last case, attribute labels are generated
     * automatically out of names.
     *
     * @param   array  $attrs   1-dimension list of attributes with labels or not.
     *
     * @return  void         
     */
    public function setAttributes(array $attrs): void
    {
        foreach ($attrs as $attr_key => $attr_value) {
            if (is_numeric($attr_key)) {
                $attr_label = Inflector::underscoreToWords($attr_value);
                $attr_name = $attr_value;
            } else {
                $attr_label = $attr_value;
                $attr_name = $attr_key;
            }
            
            $attr_name = Inflector::simplify(Inflector::camelCaseToUnderscore($attr_name));
            
            $this->attributes[$attr_name] = $attr_label;
        }
    }
    
    /**
     * Get list of attributes of the essence. Accets booleaan param $show_labels.
     * If it is set to true, returns assos array where keys are attribute names
     * and values are attrubute labels (e.g. ["first_name"=>"First name"]).
     *
     * @param   bool  $show_labels  (optional) True if attribute labels are needed.
     *
     * @return  array         
     */
    public function getAttributes(bool $show_labels = false): array
    {
        if ($show_labels) {
            return $this->attributes;
        } else {
            return array_keys($this->attributes);
        }
    }
    
    /**
     * Sets id of the essence.
     *
     * @param   string  $id     Any string to identify the essence 
     *
     * @return  void         
     */
    public function setId(string $id): void
    {
        if (mb_strlen(trim($id)) > 0) {
            $this->id = $id;
        }
    }
    
    /**
     * Returns id of the essence.
     *
     * @return  string     Id of the essence or null if it is not set.  
     */
    public function getId(): ?string
    {
        return $this->id;
    }
    
    /**
     * Sets active language of the essence.
     *
     * @param   string  $language               Language of the name variables, e.g. "en"
     * @paeam   bool    $use_exising_values     Set to true, if 
     *
     * @return  void         
     */
    public function setLanguage(string $language, bool $use_existing_values = false): void
    {
        if (mb_strlen(trim($language)) == 0) {
            return;
        }
        
        if ($this->language == $language) {
            return;
        }
        
        if ($use_existing_values == true) {
            foreach ($this->values[$this->language] as $attribute=>$value) {
                if (!(
                        array_key_exists($language, $this->values)
                        &&
                        array_key_exists($attribute, $this->values[$language])
                    )) {
                    $this->values[$language][$attribute] = $value;
                }
            }
        }
        
        $this->language = $language;
    }
    
    /**
     * Returns current language of the essence.
     *
     * @return  string     Language value
     */
    public function getLanguage(): string
    {
        return $this->language;
    }
    
    /**
     * Sets value of the essence defined by atttribute in certain language.
     * If language is not set, default is used. Attribute must be preliminary
     * set by setAttributes.
     *
     * @param   string  $attribute_name     Attribute name
     * @param   string  $value              Value
     * @param   string  $language           (optional) Language of the value. If not set,
     *                                      default is used.
     *
     * @return  void
     */
    private function setValue(string $attribute_name, string $value, string $language = ""): void
    {
        $attribute_name = Inflector::simplify(Inflector::camelCaseToUnderscore($attribute_name));
        if (mb_strlen($attribute_name) == 0) {
            return;
        }
        
        if (!in_array($attribute_name, $this->getAttributes())) {
            return;
        }
        
        if (mb_strlen(trim($language)) == 0) {
            $language = $this->getLanguage();
        }
        
        $this->values[$language][$attribute_name] = $value;
    }
    
    /**
     * Returns value of the essence set by attribute in certain language.
     * If language is not set, default is used. Attribute must be preliminary
     * set by setAttributes.
     *
     * @param   string  $attribute_name     Attribute name of the value
     * @param   string  $language           (optional) Language of the value. If not set,
     *                                      default is used.
     *
     * @return  string                      Value or null
     */
    private function getValue(string $attribute_name, string $language = ""): ?string
    {
        $attribute_name = Inflector::simplify(Inflector::camelCaseToUnderscore($attribute_name));
        if (mb_strlen($attribute_name) == 0) {
            return null;
        }
        
        if (!in_array($attribute_name, $this->getAttributes())) {
            return null;
        }
        
        if (mb_strlen(trim($language)) == 0) {
            $language = $this->getLanguage();
        }
        
        if (array_key_exists($language, $this->values) && array_key_exists($attribute_name, $this->values[$language])) {
            return $this->values[$language][$attribute_name];
        } else {
            return null;
        }
    }

    /**
     * Mass setting of values via array.
     *
     * @param   array   $values     Associative array with values, where key should
     *                              be equal to attribute name (attributes must be 
     *                              predefined with Essence::setAttributes()).
     * @param   string  $language   (optional) Language of the values. If not
     *                              set, active language is used.
     *
     * @return  void
     */
    public function setValues(array $values, string $language = ""): void
    {
        if (mb_strlen($language) == 0) {
            $language = $this->language;
        }
        
        foreach ($values as $attrubute => $value) {
            $attribute = Inflector::simplify(Inflector::camelCaseToUnderscore($attrubute));
            if (in_array($attrubute, $this->getAttributes()) && (is_string($value) || is_numeric($value))) {
                $this->values[$language][$attrubute] = $value;
            }
        }
    }

    /**
     * Returns all values of Essence.
     *
     * @param   string  $language   (optional) Language of the values. If not
     *                              set, active language is used.
     *
     * @return  array               List of values in associative array of null if
     *                              instance doesn't have values of the defined language.
     */
    public function getValues(string $language = ""): ?array
    {
        if (mb_strlen($language) == 0) {
            $language = $this->language;
        }
        
        if (!$this->isTranslated($language)) {
            return null;
        }
        
        return $this->values[$language];
    }

    /**
     * Checks if essence has values in defined language.
     *
     * @param   string  $language   Language name to ve checked, e.g. "en". Case matters
     *
     * @return  bool                True if translation exists, False if not.
     */
    public function isTranslated($language): bool
    {
        if (mb_strlen(trim($language)) == 0) {
            return false;
        }
        
        if (array_key_exists($language, $this->values) && count($this->values[$language]) > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Magic method for attribute setting. Allows to set attributes by methods
     * like setFirstName("John"), that aliases to setValue("first_name", "john");
     *
     * @return  void 
     */
    public function __call(string $name, array $arguments)
    {
        if (substr($name, 0, 3) != "set") {
            throw new Exception("Call to undefined method \"" . $name . "\" in Robin\Essence");
        }
        
        $attribute_name = Inflector::camelCaseToUnderscore(mb_substr($name, 3));
        if (!in_array($attribute_name, $this->getAttributes())) {
            throw new Exception("Cannot set undefined attribute \"" . $attribute_name . "\"");
        }
        
        if (count($arguments) == 0) {
            throw new Exception("" . $name . "() expects at least 1 parameter, 0 given");
        } else if (count($arguments) > 2) {
            throw new Exception("" . $name . "() expects at most 2 parameters, " . count($arguments) ." given");
        }
        
        if (!is_string($arguments[0]) && !is_numeric($arguments[0])) {
            throw new Exception("" . $name . "() expects parameter 1 to be string");
        }
        
        if (array_key_exists(1, $arguments)) {
            if (!is_string($arguments[1])) {
                throw new Exception("" . $name . "() expects parameter 2 to be string");
            }
        } else {
            $arguments[1] = $this->language;
        }
        
        return $this->setValue($attribute_name, $arguments[0], $arguments[1]);
    }
    
    /**
     * Magic method for attribute getting. Allows to get attributes by direct names
     * like $this->first_name, that aliases to getValue("first_name", "john");
     *
     * @return  void 
     */
    public function __get(string $name)
    {
        if (!in_array($name, $this->getAttributes())) {
            throw new Exception("Cannot read undefined attribute \"" . $name . "\"");
        }
        
        return $this->getValue($name);
    }
    
    /**
     * Magic method for attribute setting. Allows to set attributes as object variables
     * like $this->first_name = "John", that aliases to setValue("first_name", "john");
     *
     * @return  void 
     */
    public function __set(string $name, $value)
    {
        if (!in_array($name, $this->getAttributes())) {
            throw new Exception("Cannot set undefined attribute \"" . $name . "\"");
        }
        
        return $this->setValue($name, $value);
    }
    
    /**
     * Saves all values to external data source
     *
     * @return  bool    True if saving was successfull, false if not
     */
    public function save(): bool
    {
        if (mb_strlen($this->id) == 0) {
            throw new Exception("Please set id with Essence::setId() method for ". $this->category . " instance before saving");
        }
        
        $filename = $this->getFilePath($this->getId() . ".ini", true);
        $ini = "";
        
        // Composing ini source
        foreach ($this->values as $language=>$values) {
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
        
        return false;
    }
    
    /**
     * Restores all values from external data source
     *
     * @return  bool    True if restoring was successfull, false if not
     */
    public function restore(): bool
    {
        if (mb_strlen($this->id) == 0) {
            throw new Exception("Please set id with Essence::setId() method for ". $this->category . " instance before restoring");
        }
        
        $filename = $this->getFilePath($this->getId() . ".ini");

        if (file_exists($filename) && is_file($filename)) {
            $values = parse_ini_file($filename, true);
            foreach ($values as $language_from_ini => $attrubutes_from_ini) {
                if (is_array($attrubutes_from_ini)) {
                    $values_array = [ ];
                    foreach ($attrubutes_from_ini as $attrubute => $value) {
                        if (in_array($attrubute, $this->getAttributes())) {
                            $values_array[$attrubute] = $value;
                        }
                    }
                    if (count($values_array)) {
                        $this->values[$language_from_ini] = $values_array;
                    }
                }
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Returns full file path to file with data to be stored. Input file name
     * can contain subdirs. Optionally second parameter can be set to true to
     * to create all folders on way to final filename.
     *
     * @param   string  $filename           Name of the file (can include
     *                                      containing folder)
     * @param   bool    $create_folders     (optional) Set to true to create
     *                                      nonexistent folders in File Path
     *
     * @return  string                      Output string, simplified and clean
     */
    private function getFilePath(string $filename, bool $create_folders = false)
    {
        if (mb_strlen($filename) == 0) {
            throw new Exception("Filename cannot be empty");
        }
        
        if (mb_substr($filename, 0, 1) == "/") {
            return $filename;
        }
        
        // If filename path is not absolute, we identify the root dir
        if (defined("ROOT")) {
            $root_dir = ROOT;
        } else {
            $backtrace = debug_backtrace();
            $i = count($backtrace)-1;
            if (array_key_exists($i, $backtrace) && array_key_exists("file", $backtrace[$i])) {
                $root_dir = dirname($backtrace[$i]["file"]);
            } else {
                $root_dir = __DIR__;
            }
        }
        
        // Building file path from root as array of folders that inclose each next one
        $folders = [ ];
        if (mb_strlen(self::DIR) > 0) {
            $folders[] = self::DIR;
        }
        if (is_string($this->category) && mb_strlen($this->category) > 0) {
            $folders[] = $this->category;
        }
        
        // Taking out prefix folder (or several) from input $filename and put
        // them into $folders array by poping out last element (as filename) and
        // merging with $folders
        $filename_parts = explode("/", $filename);
        $filename = array_pop($filename_parts);
        $folders = array_merge($folders, $filename_parts);
        
        // Simplifying filename to cut away dagnerous symbols. If filename has
        // extension, we simplify it separately.
        $filename_ext = mb_strrpos($filename, ".") ? mb_strcut($filename, mb_strrpos($filename, ".")) : false;
        if ($filename_ext) {
            $filename = Inflector::simplify(mb_substr($filename, 0, (-1)*mb_strlen($filename_ext)));
            $filename .= "." . Inflector::simplify($filename_ext);
        } else {
            $filename = Inflector::simplify($filename);
        }
        
        // Iterating folders one-by-one, adding to root, check existance and create if needed
        $folder_path = $root_dir;
        foreach($folders as $folder) {
            if ($folder == "." || $folder == "..") {
                continue;
            }
            $folder = Inflector::simplify($folder);
            if (mb_strlen($folder) == 0) {
                continue;
            }
            $folder_path .= "/" . $folder;
            if (!is_dir($folder_path) && $create_folders) {
                mkdir($folder_path, 0744);
            }
        }
        
        return $folder_path . "/" . $filename;
    }

    /**
     * Returns all values of the Essence
     *
     * @return  array          Content of $this->values
     */ 
    public function export()
    {
        return $this->values;
    }

}