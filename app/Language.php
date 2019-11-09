<?php

namespace Robin;

use \Exception;
use \Robin\Keeper;

trait Language
{
    protected $data_handler;
    protected $language;
    protected $values;
    
    /**
     * Sets active language of the object.
     *
     * @param   string  $language               Language of the name variables, e.g. "en"
     * @paeam   bool    $use_exising_values     Set to true, if 
     *
     * @return  void         
     */
    public function setLanguage(string $language, bool $use_existing_values = false): void
    {
        if (strlen(trim($language)) == 0) {
            return;
        }
        
        if ($this->language == $language) {
            return;
        }
        
        if ($use_existing_values == true) {
            foreach ($this->values[$this->language] as $attribute=>$value) {
                if (!$this->isTranslated($language, $attribute)) {
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
     * Checks if essence has values in defined language.
     *
     * @param   string  $language   Language name to ve checked, e.g. "en". Case matters
     * @param   string  $attrubute  (optional) Name of the attribute to be checked.
     *                              If is set, then method checks existance of
     *                              attribute, not just language.
     * @return  bool                True if translation exists, False if not.
     */
    public function isTranslated($language, string $attribute = null): bool
    {
        if (strlen(trim($language)) == 0) {
            return false;
        }
        
        if (array_key_exists($language, $this->values) && count($this->values[$language]) > 0) {
            
            // Checking if second argument of method is set. If not, return true.
            if ($attribute == null) {
                return true;
            }
            
            if (array_key_exists($attribute, $this->values[$language])) {
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
     * @return  bool    True if restoring was successfull, false if not
     */
    public function read(): bool
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
        
        if (is_array($values)) {
            $count_values = 0;
            foreach ($values as $language => $attrubutes) {
                if (is_array($attrubutes)) {
                    $values_array = [ ];
                    foreach ($attrubutes as $attrubute => $value) {
                        if (in_array($attrubute, $this->getAttributes())) {
                            $values_array[$attrubute] = $value;
                        }
                    }
                    if (count($values_array)) {
                        $this->values[$language] = $values_array;
                        $count_values++;
                    }
                }
            }
            
            if ($count_values) {
                return true;
            }
        }
        
        return false;
    }
    
}