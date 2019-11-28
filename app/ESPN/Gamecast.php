<?php

namespace Robin\ESPN;

use \Exception;
use \Robin\Logger;
use \Robin\ESPN\Parser;

class Gamecast
{
    use Logger;
    
    const METHODS = [ "header", "quarters", "leaders", "drives" ];
    
    private $schedule_time;
    public $values = [ ];
    
    /**
     * Class constructor
     *
     * @param   string  $url            URL of the page to get info
     * @param   string  $language       Language of the page
     */
    public function __construct(string $url, string $language)
    {
        $parser = new Parser($url, $language);
        
        $this->schedule_time = $parser->getScheduleTime("Europe/Moscow");
        $this->home_team = $parser->getHomeTeam();
        $this->away_team = $parser->getAwayTeam();
        $this->score = $parser->getScore();
        debug($parser->getHomeReceivingLeader());
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
    
    public function getValues(): array
    {
        return [ $this->schedule_time, $this->home_team, $this->away_team, $this->score ];
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
    	return [ ];
    }
    
    private function import(array $values): void
    {
    	
    }
    
    public function header(): array
    {
    	return [ ];
    }
    
    public function quarters(): array
    {
    	return [ ];
    }
    
    public function leaders(): array
    {
    	return [ ];
    }
    
    public function drives(): array
    {
    	return [ ];
    }

}