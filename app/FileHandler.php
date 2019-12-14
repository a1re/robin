<?php

namespace Robin;

use \Exception;
use \Robin\Logger;
use \Robin\Interfaces\DataStorage;
use \Robin\Inflector;

 /**
  * Data keeping class for saving and restoring data of Essences, Drives and Plays.
  * 
  * @package    Robin
  * @author     Yuriy Marin <yuriy.marin@gmail.com>
  */
class FileHandler implements DataStorage
{
    use Logger;
    
    public $dir;
    
    /**
     * Class constructor
     *
     * @param   string  $dir    Directory relativly to project root
     */
    public function __construct(string $dir = "")
    {
        $root_dir = self::getRoot();
        
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
     * STATIC METHOD
     * Returns project root dir
     *
     * @return  string                project root dir
     */
    public static function getRoot(): string
    {
        // If environment doesn't have constant ROOT with root dir, we find it 
        // manualy with debug_backtrace;
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
        
        if (substr($root_dir, -1) == "/") {
            $root_dir = substr($root_dir, 0, -1);
        }
        
        return $root_dir;
    }
    
    /**
     * Writes data for object in INI format
     *
     * @param   string  $object_id  Identifier of the object, without file extension
     *
     * @return  bool                TRUE on success, FALSE if data was not written
     */    
    public function save(string $object_id, array $values): bool
    {
        if (strlen($object_id) == 0) {
            throw new Exception("Please set id of the object to be saved");
        }
        
        $filepath = $this->getFilePath($object_id, "ini", true);
        $ini = "";
        
        // Composing ini source
        foreach ($values as $locale=>$values) {
            if (is_array($values)) {
                $ini .= "[" . $locale . "]" . PHP_EOL;            
                foreach ($values as $attrubute => $translation) {
                    if (!is_string($translation) && !is_numeric($translation)) {
                        continue;
                    }
                    $translation = str_replace(PHP_EOL, " ", $translation);
                    $translation = addslashes($translation);
                    $ini .= $attrubute . " = \"" . $translation . "\";" . PHP_EOL;
                }
                $ini .= PHP_EOL;
            }
        }
        
        // If nothing to write, just return false
        if (strlen(trim($ini)) == 0) {    
            return false;
        }
        
        $fp = fopen($filepath, "w");
        if ($fp && flock($fp, LOCK_EX)) {
            fwrite($fp, $ini);
            flock($fp, LOCK_UN);
            fclose($fp);
            chmod($filepath, 0744);
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Removes data of the object (deletes ini file)
     *
     * @param   string  $object_id  Identifier of the object, without file extension
     *
     * @return  bool                TRUE on success, FALSE if file was not found or error
     */    
    public function remove(string $object_id): bool
    {
        if (strlen($object_id) == 0) {
            throw new Exception("Please set id of the object to be saved");
        }
        
        $filepath = $this->getFilePath($object_id, "ini", true);
        if (file_exists($filepath) && is_file($filepath)) {
            return unlink($filepath);
        }
        
        return false;
    }

    /**
     * Reads data for object, stored in INI format
     *
     * @param   string  $object_id  Identifier of the object, without file extension
     *
     * @return  array               Data in array format or FALSE if not found/read
     */
    public function read(string $object_id)
    {
        if (strlen($object_id) == 0) {
            throw new Exception("Please set id of the object to be read");
        }
        
        $filepath = $this->getFilePath($object_id, "ini");

        if (file_exists($filepath) && is_file($filepath)) {
            return parse_ini_file($filepath, true);
        }
        
        return false;
    }

    /**
     * Reads source of the file
     *
     * @param   string  $filename   Identifier of the object, without file extension
     * @return  string              Files source or null
     */
    public function readSource(string $filename): ?string
    {
        if (strlen($filename) == 0) {
            throw new Exception("Please set id of the object to be read");
        }
        
        $filepath = $this->getFilePath($filename);
        if (file_exists($filepath) && is_file($filepath)) {
            $file_handler = fopen($filepath, "r");
            $file_contents = fread($file_handler, filesize($filepath));
            fclose($file_handler);
            return $file_contents;
        }
        
        return null;
    }

    /**
     * Writes source to the file
     *
     * @param   string  $filename      Identifier of the object, without file extension
     * @param   string  $source         Source to be written to the file
     * @return  boolean                 Data in array format or FALSE if not found/read
     */
    public function saveSource(string $filename, string $source): bool
    {
        if (strlen($filename) == 0) {
            throw new Exception("Please set id of the object to be saved");
        }
        
        $filepath = $this->getFilePath($filename, null, true);
        
        $file_handler = fopen($filepath, "w");
        if ($file_handler && flock($file_handler, LOCK_EX)) {
            $result = fwrite($file_handler, $source);
            flock($file_handler, LOCK_UN);
            fclose($file_handler);
            if ($result) {
                chmod($filepath, 0744);
                return true;                
            }
        }
        
        return false;
    }
    
    /**
     * Returns full path to file with data. Input file name can contain subdirs. 
     * Optionally second parameter can be set to true to create all folders
     * on way to final filename.
     *
     * @param   string  $filename           Name of the file (can include containing
     *                                      folder)
     * @param   string  $extension          Extenstion of the file, without leading dot
     * @param   bool    $create_folders     (optional) Set to true to create non-exist
     *                                      folders in File Path
     * @return  string                      Output string, simplified and clean
     */
    public function getFilePath(string $filename, string $extension = null, bool $create_folders = false): string
    {
        if (mb_strlen($filename) == 0) {
            throw new Exception("Filename cannot be empty");
        }
        
        if (mb_substr($filename, 0, 1) == "/") {
            return $filename;
        }
        
        if (strlen($extension) > 0) {
            // Checking filename extension and adding "ini" if there is no one
            $last_segment = mb_substr($filename, mb_strrpos($filename, "/")+1 ?? 0);
            // There is a chance that las segment can start with smth like A.J., so
            // we need to cut it off for proper extenstion search
            if (preg_match("/[a-z]{1}\.\s?[a-z]{1}\./i", mb_substr($last_segment, 0, 5))) {
                $last_segment = mb_substr($last_segment, 5);
            }
            $last_segment_extension = mb_substr($last_segment, mb_strrpos($last_segment, ".") + 1);
            if (mb_strrpos($last_segment, ".") === false && $last_segment_extension !== $extension) {
                $filename .= "." . $extension;
            }
        }
        
        // Taking out prefix folder (or several) from input $filename and put
        // them into $folders array by poping out last element (as filename) and
        // merging with $folders
        $folders = explode("/", $filename);
        $filename = array_pop($folders);
    
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
        $folder_path = $this->dir;
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
}