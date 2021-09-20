<?php

namespace Robin;

use \Exception;

 /**
  * Set of functions to manipulate words
  * 
  * @package    Robin
  * @author     Yuriy Marin <yuriy.marin@gmail.com>
  */
class Inflector
{
    
    /**
     * STATIC METHOD
     *
     * Cleans str from all chars except regular latin and underscore, converts
     * camel case to snake case.
     *
     * @param   string  $str    Input string
     * @return  string          Output string, simplified and clean
     */
    public static function simplify(string $str): string
    {
        $str = self::clean($str);
        $str = str_replace(["'", "`", "′", "&"], "", $str);
        $str = trim(preg_replace("/[^\w]+/", " ", $str));
        $str = mb_convert_case($str, MB_CASE_LOWER, "UTF-8");
        $str = str_replace(" ", "_", $str);
        return $str;
    }

    /**
     * STATIC METHOD
     * 
     * Replaces accented characters with their regular latin analogs and removes
     * html entities.
     *
     * @param   string  $str    Input string
     * @return  string          Output string, without accented characters and
     *                          html entities
     */
    public static function clean(string $str): string
    {
        $str = preg_replace("/&#?[a-z0-9]+;/i", "", $str); 
        $str = strtr(
            utf8_decode($str),
            utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'),
            'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY'
        );
        return $str;
    }
    
    /**
     * STATIC METHOD
     *
     * Converts string in camelCase to snake_case. Ignores whitespaces.
     *
     * @param   string  $str    Input string in camelCase
     * @return  string          Output string in snake_case
     */    
    public static function camelCaseToUnderscore(string $str): string
    {
        $words = preg_split("/((?<=[a-z])(?=[A-Z])|(?=[A-Z][a-z]))/", $str);
        $words_count = count($words);
        for ($i=0; $i<$words_count; $i++) {
            $words[$i] = trim($words[$i]);
            if (mb_strlen($words[$i]) == 0) {
                unset($words[$i]);
                continue;
            }
            
            $words[$i] = mb_convert_case($words[$i], MB_CASE_LOWER, "UTF-8");
        }
        
        return preg_replace("/_+/", "_", implode("_", $words));
    }

    /**
     * STATIC METHOD
     *
     * Converts snake_case to camelCase. Ignores whitespaces.
     *
     * @param   string  $str    Input string in snake_case
     * @return  string          Output string in camelCase
     */  
    public static function underscoreToCamelCase(string $str): string
    {
        $words = explode("_", $str);
        
        if (count($words) > 1) {
            for ($i=0; $i<count($words); $i++) {
                $words[$i] = trim($words[$i]);
                if (mb_strlen($words[$i]) == 0) {
                    unset($words[$i]);
                    continue;
                }
                
                $words[$i] = mb_convert_case($words[$i], MB_CASE_LOWER, "UTF-8");
                if ($i > 0) {
                    $words[$i] = mb_convert_case($words[$i], MB_CASE_TITLE, "UTF-8");
                }
            }
            return implode("", $words);
        } else {
            return $words[0];
        }
    }

    /**
     * STATIC METHOD
     *
     * Converts snake_case to whitespace separated words
     *
     * @param   string  $str    Input string in snake_case
     * @return  string          Output string in separated words
     */
    public static function underscoreToWords(string $str): string
    {
        $words = preg_split('/(_|\s)/', $str);
        $words[0] = mb_convert_case($words[0], MB_CASE_TITLE, "UTF-8");
        if (count($words) > 1) {
            return implode(" ", $words);
        } else {
            return $words[0];
        }
    }
    
    /**
     * STATIC METHOD
     *
     * Selects plural form for passed $number according to its ending
     * by $rules array.
     *
     * @param    int      $number    Number to base word form on
     * @param    array    $rules     Array of rules, where keys are endings
     *                               of numbers and values are word forms,
     *                               e.g. [1=>"mouse", 2=>mice, ...]. Endings
     *                               matching one-by-one and if nothing was
     *                               found, function returns last form.
     * @return   string              Word form
     */
    public static function pluralize(int $number, array $rules): string
    {
        foreach ($rules as $ending => $form) {
            $n = mb_strlen($ending);
            if (substr($number, (-1)*$n) == $ending) {
                return $form;
            }
        }
        return (string) end($rules);
    }
    
}