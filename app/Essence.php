<?php

namespace Robin;

use \Exception;
use \Robin\Interfaces\Translatable;
use \Robin\Language;
use \Robin\Logger;
use \Robin\Inflector;
use \Robin\Keeper;

 /**
  * Essence class for basing on it different objects like Players, Teams, etc.
  * 
  * @package    Robin
  * @author     Yuriy Marin <yuriy.marin@gmail.com>
  */
class Essence implements Translatable
{
    use Logger;
    
    private $attributes = [];
    
    protected $id;    
    protected $values;
    protected $category;
    protected $data_handler;
    protected $locale;
    protected $language;
    protected static $default_language = "en_US";
    
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
        $this->setLocale(self::$default_language);
    }
    
    /**
     * Import essence values. Can be used array from Essence::export(). Pleas note
     * that it imports only attributes that were defined in Essence::setAttributes();
     * If no values of default language were imported, reset the language to actual.
     *
     * @param   array   $values     Array from Essence::export() or associative array
     *                              with values, grouped by locale.
     * @return  bool                Returns true if values were successfully imported
     *                              and false if not.
     */
    protected function import(array $import): bool
    {
        $language = null;
        if (array_key_exists("language", $import) && !is_array($import["language"]) && strlen($import["language"]) > 0) {
            $language = $import["language"];
            unset($import["language"]);
        }
        
        $locale = null;
        if (array_key_exists("locale", $import) && !is_array($import["locale"]) && strlen($import["locale"]) > 0) {
            $locale = $import["locale"];
            unset($import["locale"]);
        }
        
        if (array_key_exists("id", $import) && !is_array($import["id"]) && strlen($import["id"]) > 0) {
            $this->id = $import["id"];
            unset($import["id"]);
        }
        
        $imported_locales_list = [ ];
        foreach ($import as $locale => $values) {
            if (!is_array($values)) {
                continue;
            }
            if ($this->setValues($values, $locale)) {
                $imported_locales_list[] = $locale;
            }
        }
        
        if (count($imported_locales_list) == 0) {
            return false;
        }
        
        if ($language == null || !array_key_exists($language, $this->values)) {
            $language = reset($imported_locales_list);
        }
        
        if ($locale == null || !array_key_exists($locale, $this->values)) {
            $locale = $language;
        }
        
        $this->language = $language;
        $this->locale = $locale;
        
        return true;
    }
    
    /**
     * STATIC METHOD
     * Sets the default language for all future instances of Essence.
     *
     * @param   string  $language   Default language, e.g. "en"
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
     * If it is set to true, returns associative array where keys are attribute names
     * and values are attrubute labels (e.g. ["first_name"=>"First name"]).
     *
     * @param   bool  $show_labels  (optional) True if attribute labels are needed.
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
     * @return  void         
     */
    public function setId(string $id): void
    {
        $this->log(Inflector::clean($id));
        if (strlen(trim($id)) > 0) {
            //$this->log($id);
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
     * @return  void
     */
    private function setValue(string $attribute_name, string $value, string $locale = ""): void
    {
        $attribute_name = Inflector::simplify(Inflector::camelCaseToUnderscore($attribute_name));
        if (strlen($attribute_name) == 0) {
            return;
        }
        
        if (!in_array($attribute_name, $this->getAttributes())) {
            return;
        }
        
        if (strlen(trim($locale)) == 0) {
            $locale = $this->locale;
        }
        
        $this->values[$locale][$attribute_name] = $value;
    }
    
    /**
     * Returns value of the essence set by attribute in certain language.
     * If language is not set, default is used. Attribute must be preliminary
     * set by setAttributes.
     *
     * @param   string  $attribute_name     Attribute name of the value
     * @param   string  $locale             (optional) Locale of the value. If not set,
     *                                      default is used.
     * @return  string                      Value or null
     */
    private function getValue(string $attribute_name, string $locale = ""): ?string
    {
        $attribute_name = Inflector::simplify(Inflector::camelCaseToUnderscore($attribute_name));
        if (strlen($attribute_name) == 0) {
            return null;
        }
        
        if (!in_array($attribute_name, $this->getAttributes())) {
            return null;
        }
        
        if (strlen(trim($locale)) == 0) {
            $locale = $this->locale;
        }
        
        if (!array_key_exists($locale, $this->values)) {
            $locale = $this->language;
        }
        
        if (array_key_exists($attribute_name, $this->values[$locale])) {
            return $this->values[$locale][$attribute_name];
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
     * @param   string  $locale     (optional) Locale of the values. If not set,
     *                              active locale is used.
     * @return  bool                Returns true if values were set and false if no
     *                              values were set.
     */
    public function setValues(array $values, string $locale = ""): bool
    {
        if (strlen($locale) == 0) {
            $locale = $this->locale;
        }
        
        $count_set_values = 0;
        foreach ($values as $attrubute => $value) {
            $attribute = Inflector::simplify(Inflector::camelCaseToUnderscore($attrubute));
            if (in_array($attrubute, $this->getAttributes()) && (is_string($value) || is_numeric($value))) {
                $this->values[$locale][$attrubute] = $value;
                $count_set_values++;
            }
        }
        
        return (bool) $count_set_values;
    }

    /**
     * Returns all values of Essence.
     *
     * @param   string  $locale     (optional) Localization of the values. If not
     *                              set, active locale is used.
     * @return  array               List of values in associative array of null if
     *                              instance doesn't have values of the defined language.
     */
    public function getValues(string $locale = ""): ?array
    {
        if (strlen($locale) == 0) {
            $locale = $this->locale;
        }
        
        if (array_key_exists($locale, $this->values)) {
            return $this->values[$locale];
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
        if (substr($name, 0, 3) == "get") {
            $attribute_name = Inflector::camelCaseToUnderscore(mb_substr($name, 3));
            if (in_array($attribute_name, $this->getAttributes())) {
                return $this->getValue($attribute_name);
            } else {
                throw new Exception("Call to undefined method \"" . $name . "\" in Robin\Essence");
            }
        }
        
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
     * Magic method for attribute getting in source version without localization
     *
     * @return  void 
     */
    public function __get(string $name)
    {
        if (!in_array($name, $this->getAttributes())) {
            throw new Exception("Cannot read undefined attribute \"" . $name . "\"");
        }
        
        return $this->getValue($name, $this->language);
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
        
        return $this->setValue($name, $value, $this->language);
    }
    
    /**
     * Returns all values of the Essence
     *
     * @return  array          Content of $this->values
     */ 
    public function export()
    {
        $export = $this->values;
        $export["language"] = $this->language;
        if ($this->getId()) {
            $export["id"] = $this->getId();
        }
        if (isset($this->locale)) {
            $export["locale"] = $this->locale;
        }
        return $export;
    }
    
    /**
     * Sets locale of the object.
     *
     * @param   string  $locale                 Locale of the name variables, e.g. "en_US"
     * @paeam   bool    $use_exising_values     Set to true, if 
     *
     * @return  bool                            True if locale was successfully set, false if not         
     */
    public function setLocale(string $locale, bool $use_existing_values = false): bool
    {
        if (strlen(trim($locale)) == 0) {
            return false;
        }
        
        if ($locale == $this->language) {
            $this->locale = $locale;
            return true;
        }
        
        $attributes_set = $this->read($locale);
        
        if ($use_existing_values == true) {
            foreach ($this->values[$this->locale] as $attribute=>$value) {
                if (!$this->isTranslated($locale, $attribute)) {
                    $this->values[$locale][$attribute] = $value;
                }
            }
            $attributes_set = true;
        }
        
        if ($attributes_set) {
            $this->locale = $locale;
        }
        
        return $attributes_set;
    }
    
    /**
     * Returns current locale of the essence.
     *
     * @return  string     Locale value
     */
    public function getLocale(): string
    {
        return $this->locale;
    }
    
    /**
     * Set hadler for saving and restoring data of the Essence
     *
     * @param   Keeper  $data_handler   Keeper object for storing data
     *
     * @return  void         
     */
    public function setDataHandler(Keeper $data_handler): void
    {
        $this->data_handler = $data_handler;
    }

    /**
     * Checks if essence has values in defined locale.
     *
     * @param   string  $locale     Locale name to ve checked, e.g. "en_US". Case matters
     * @param   string  $attrubute  (optional) Name of the attribute to be checked.
     *                              If is set, then method checks existance of
     *                              attribute, not just locale.
     * @return  bool                True if translation exists, False if not.
     */
    public function isTranslated($locale, string $attribute = null): bool
    {
        if (strlen(trim($locale)) == 0) {
            return false;
        }
        
        if (array_key_exists($locale, $this->values) && count($this->values[$locale]) > 0) {
            
            // Checking if second argument of method is set. If not, return true.
            if ($attribute == null) {
                return true;
            }
            
            if (array_key_exists($attribute, $this->values[$locale])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Saves all values to external data source
     *
     * @return  bool    True if saving was successfull, false if not
     */
    public function save(): bool
    {
        if (!$this->data_handler) {
            throw new Exception("Please set handler with Essence::setDataHandler() method to save data");
        }
        
        if (strlen($this->id) == 0) {
            throw new Exception(
                "Please set id with Essence::setId() method for ".
                $this->category .
                " instance to save data"
            );
        }
        
        $object_id = $this->category . "/" . $this->id;
        
        return $this->data_handler->save($object_id, $this->values);
    }
    
    /**
     * Restores all values from external data source
     *
     * @param   string  $locale     (optional) If set, method will check read
     *                              data for defined locale and return true if
     *                              it was read and false if not. If not defined,
     *                              method will return overall result.
     * @return  bool                True data was succefully read, false if nod
     */
    public function read(string $locale = ""): bool
    {
        if (!$this->data_handler) {
            throw new Exception("Please set handler with Essence::setDataHandler() method to read data");
        }
        
        if (strlen($this->id) == 0) {
            throw new Exception(
                "Please set id with Essence::setId() method for ".
                $this->category .
                " instance to read data"
            );
        }
        
        $object_id = $this->category . "/" . $this->id;
        $values = $this->data_handler->read($object_id);
        
        if (!is_array($values) || count($values) == 0) {
            return false;
        }
        
        $count_values = 0;
        foreach ($values as $locale => $attrubutes) {
            if (is_array($attrubutes)) {
                $values_array = [ ];
                foreach ($attrubutes as $attrubute => $value) {
                    if (in_array($attrubute, $this->getAttributes())) {
                        $values_array[$attrubute] = $value;
                    }
                }
                if (count($values_array)) {
                    // To avoid overwriting with empty strings, we have to go one by one
                    foreach ($values_array as $attribute => $value) {
                        if (strlen($value) > 0) {
                            $this->values[$locale][$attribute] = $value;
                        }
                    }
                    $count_values++;
                }
            }
        }
        
        if (strlen($locale) > 0) {
            return $this->isTranslated($locale);
        }
        
        return (bool) $count_values;
    }
    
    /**
     * Creates query string of values for composition link to edit.php. This allows
     * to create ini file with localisation by clicking on a link in a parsed page.
     *
     * @return  string                string with set of values, e.g. id=..&category=..&
     */    
    public function getCompositionLinkValues(): string
    {
        $values = [ ];
        $values[] = "id=" . urlencode($this->id);
        $values[] = "category=" . urlencode($this->category);
        $values[] = "language=" . urlencode($this->language);
        $values[] = "locale=" . urlencode($this->locale);
        
        $attributes = $this->getAttributes();
        $values[] = "attributes=" . join(",", $attributes);
        foreach ($attributes as $attribute) {
            if ($this->$attribute != null) {
                $values[] = $attribute . "=" . urlencode($this->$attribute);
            }
        }
        return join("&", $values);
    }

}