<?php

namespace Robin;

use \Exception;
use \Robin\Exceptions\ParsingException;
use \Robin\Interfaces\Translatable;
use \Robin\Language;
use \Robin\Logger;
use \Robin\Inflector;

 /**
  * Essence class for basing on it different objects like Players, Teams, etc.
  * 
  * @package    Robin
  * @author     Yuriy Marin <yuriy.marin@gmail.com>
  */
class Essence implements Translatable
{
    use Logger;
    use Language;
    
    private $id;
    private $attributes = [];
    
    protected $values;
    protected $category;
    protected static $default_language = "en";
    
    /**
     * Class constructor.
     *
     * @param   string  $category   Category of the essence, in plural form (e.g. "Players")
     */
    public function __construct(string $category = "Essences")
    {
        if (strlen($category) == 0) {
            throw new Exception("Category of the Essence cannot be empty");
        }
        
        $this->category = $category;
        $this->language = self::$default_language;
    }
    
    /**
     * STATIC METHOD
     * Sets the default language for all future instances of Essence.
     *
     * @param   string  $language   Default language, e.g. "en"
     *
     * @return  void         
     */    
    public static function setDefaultLanguage(string $language): void
    {
        if (strlen($language) == 0) {
            throw new Exception("Default language for Essence cannot be empty");
        }
        
        self::$default_language = $language;
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
        if (strlen(trim($id)) > 0) {
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
        if (strlen($attribute_name) == 0) {
            return;
        }
        
        if (!in_array($attribute_name, $this->getAttributes())) {
            return;
        }
        
        if (strlen(trim($language)) == 0) {
            $language = $this->language;
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
        if (strlen($attribute_name) == 0) {
            return null;
        }
        
        if (!in_array($attribute_name, $this->getAttributes())) {
            return null;
        }
        
        if (strlen(trim($language)) == 0) {
            $language = $this->language;
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
        if (strlen($language) == 0) {
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
        if (strlen($language) == 0) {
            $language = $this->language;
        }
        
        if (array_key_exists($language, $this->values)) {
            return $this->values[$language];
        } else {
            return null;
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
     * Returns all values of the Essence
     *
     * @return  array          Content of $this->values
     */ 
    public function export()
    {
        return $this->values;
    }

}