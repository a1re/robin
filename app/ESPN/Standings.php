<?php

namespace Robin\ESPN;

use \Exception;
use \Robin\Logger;
use \Robin\Team;
use \Robin\Inflector;
use \Robin\Keeper;
use \Robin\FileHandler;
use \Robin\ESPN\Parser;

class Standings {
    use Logger;
    
    const METHODS = [ "tables" ];
    private $keeper;
    
    /**
     * Class constructor
     *
     * @param   string  $url            URL of the page to get info
     * @param   string  $language       Source language of the page
     * @param   string  $locale         (optional) Locale of the parsed data
     */
    public function __construct($url, string $language, string $locale = "")
    {
        $this->keeper = new Keeper(new FileHandler("data"));
        
        if (is_array($url)) {
            $this->import($url);
            return;
        }
        
        $parser = new Parser($url, $language);
        $parser->setDatahandler($this->keeper);
        if(strlen($locale) > 0 && $locale != $language) {
            $parser->setLocale($locale);
        }

        $this->tables = $parser->getTablesList();
    }
    
    /**
     * Returns type of the object
     *
     * @return  string        Object type name
     */
    public function getType(): string
    {
        return "Gamecast";
    }
    
    /**
     * List of public methods available for calling
     *
     * @return  array   List of methods
     */
    public function getMethods(): array
    {
      return self::METHODS;
    }
    
    public function export(): array
    {
        $export = [ ];
        return $export;
    }
    
    public function import(array $values): void
    {

    }

    public function tables(): array
    {
      $tables = [];

      foreach ($this->tables as $table_values) {
        $table = [
          "name" => $table_values["name"],
          "divisions" => []
        ];

        foreach ($table_values["divisions"] as $division_values) {
          $division = [
            "rows" => []
          ];
          
          if (array_key_exists("name", $division_values)) {
            $division["name"] = $division_values["name"];
          }

          foreach ($division_values["teams"] as $team_values) {
            $row = [
              "logo" => $team_values["logo"],
              "team" => $team_values["team"]->getShortName(),
              "rank" => $team_values["team"]->rank,
              "conference" => $team_values["values"]["conference"],
              "overall" => $team_values["values"]["overall"],
              "home" => $team_values["values"]["home"],
              "away" => $team_values["values"]["away"],
              "streak" => $team_values["values"]["streak"]
            ];

            array_push($division["rows"], $row);
          }

          array_push($table["divisions"], $division);
        }

        array_push($tables, $table);
      }
      return [ "table_list" => $tables ];
    }
}