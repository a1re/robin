<?
namespace Robin\ESPN;

use \Exception;
use \Robin\Play;
use \Robin\Team;
use \Robin\Player;

class Decompose
{
    public static $possessing_team = null;
    public static $defending_team = null;
    const NAME_PATTERN_2W = "[a-zA-Z-.\']+\s[a-zA-Z-.\']+";
    const NAME_PATTERN_3W = "[a-zA-Z-.\']+\s[a-zA-Z-.\']+[a-zA-Z-.\'\s]*";
    
    /**
     * STATIC METHOD
     * Sets the default value of possessing team for all future instances
     * of docomposed Plays
     *
     * @param   string  $possessing_team        Possessing team object
     * @return  void
     */
    public static function setPossessingTeam(Team $possessing_team): void
    {
        self::$possessing_team = $possessing_team;
    }
    
    /**
     * STATIC METHOD
     * Sets the default value of defending team for all future instances
     * of docomposed Plays
     *
     * @param   string  $defending_team        Possessing team object
     * @return  void
     */
    public static function setDefendingTeam(Team $defending_team): void
    {
        self::$defending_team = $defending_team;
    }
    
    /**
     * Converts textual touchdown description to instance of Robin\Play. Takes
     * scoring event description, preferably with cut off conversion description, e.g.
     * "Samson Ebukam 25 Yd Interception Return". If string will contain conversion
     * description, not wrapped by brackets, it may cause inaccuracies in parsing and
     * decomposing into Robin\Play object. It'sbetter to use Decompose::XP first and
     * cut off the extra-point(s) part by origin decomposed by Decompose::XP.
     *
     * @param   string  $scoring_description    Full textual description of TD, incl. XP
     * @return  Play                            Instance of Robin\Play or null
     */
    public static function TD(string $scoring_description): ?Play
    {
        self::checkDefaultTeams();
        
        // Run play touchdown
        $pattern = "(" . self::NAME_PATTERN_3W . ")\s\d{1,3}\sya?r?ds?\srun";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $play = new Play(Play::RUN, self::$possessing_team, self::$defending_team);
            $play->setScoringMethod(Play::TD);
            $play->setOrigin($matches[0]);
            $play->setAuthor(self::makePlayer($matches[1], self::$possessing_team->getId() . "/" . $matches[1]));
            return $play;
        }
        
        // Pass play touchdown
        $pattern = "(" . self::NAME_PATTERN_3W . ")\s\d{1,3}\sya?r?ds?\spass\sfrom\s(" . self::NAME_PATTERN_3W . ")";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $play = new Play(Play::PASS, self::$possessing_team, self::$defending_team);
            $play->setScoringMethod(Play::TD);
            $play->setOrigin($matches[0]);
            $play->setAuthor(self::makePlayer($matches[1], self::$possessing_team->getId() . "/" . $matches[1]));
            $play->setPasser(self::makePlayer($matches[2], self::$possessing_team->getId() . "/" . $matches[2]));
            return $play;
        }
        
        // Interception return touchdown
        $pattern = "(" . self::NAME_PATTERN_3W . ")\s\d{1,3}\sya?r?ds?\sinterception\sreturn";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $play = new Play(Play::INTERCEPTION_RETURN, self::$possessing_team, self::$defending_team);
            $play->setScoringMethod(Play::TD);
            $play->setOrigin($matches[0]);
            $play->setAuthor(self::makePlayer($matches[1], self::$possessing_team->getId() . "/" . $matches[1]));
            return $play;
        }
        
        // Fumble return touchdown
        $pattern = "(" . self::NAME_PATTERN_3W . ")\s\d{1,3}\sya?r?ds?\sfumble\sreturn";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $play = new Play(Play::FUMBLE_RETURN, self::$possessing_team, self::$defending_team);
            $play->setScoringMethod(Play::TD);
            $play->setOrigin($matches[0]);
            $play->setAuthor(self::makePlayer($matches[1], self::$possessing_team->getId() . "/" . $matches[1]));
            return $play;
        }
        
        // Fumble recovery touchdown
        $pattern = "(" . self::NAME_PATTERN_3W . ")\s\d{1,3}\sya?r?ds?\sfumble\srecovery";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $play = new Play(Play::FUMBLE_RECOVERY, self::$possessing_team, self::$defending_team);
            $play->setScoringMethod(Play::TD);
            $play->setOrigin($matches[0]);
            $play->setAuthor(self::makePlayer($matches[1], self::$possessing_team->getId() . "/" . $matches[1]));
            return $play;
        }
        
        // Punt return touchdown
        $pattern = "(" . self::NAME_PATTERN_3W . ")\s\d{1,3}\sya?r?ds?\spunt\sreturn";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $play = new Play(Play::PUNT_RETURN, self::$possessing_team, self::$defending_team);
            $play->setScoringMethod(Play::TD);
            $play->setOrigin($matches[0]);
            $play->setAuthor(self::makePlayer($matches[1], self::$possessing_team->getId() . "/" . $matches[1]));
            return $play;
        }
        
        // Kickoff return touchdown
        $pattern = "(" . self::NAME_PATTERN_3W . ")\s\d{1,3}\sya?r?ds?\skickoff\sreturn";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $play = new Play(Play::KICKOFF_RETURN, self::$possessing_team, self::$defending_team);
            $play->setScoringMethod(Play::TD);
            $play->setOrigin($matches[0]);
            $play->setAuthor(self::makePlayer($matches[1], self::$possessing_team->getId() . "/" . $matches[1]));
            return $play;
        }
        
        $play = new Play(Play::OTHER, self::$possessing_team, self::$defending_team);
        $play->setScoringMethod(Play::TD);
        $play->setOrigin($scoring_description);  

        return $play;
    }

    /**
     * Converts textual extra point description to instance of Robin\Play. Takes
     * full description, e.g. "Samson Ebukam 25 Yd Interception Return (Greg Zuerlein Kick)",
     * parses only extra point description (incl. two-point conversions) and decomposes
     * into Robin\Play object. Some of the descriptions on ESPN lacks brackets, this
     * method works with such cases, but the if first name mentioned in XP contain more
     * then two words (e.g. "Will Fuller V run" or "Patrick Mahomes III pass to Tyreek Hill"),
     * it may cause inaccuracies.
     *
     * @param   string  $scoring_description    Full textual description of TD, incl. XP
     * @return  Play                            Instance of Robin\Play or null
     */
    public static function XP(string $scoring_description): ?Play
    {
        self::checkDefaultTeams();
        
        // One-point conversion is good
        if (preg_match("/\(?(" . self::NAME_PATTERN_2W .")\skick(?:\sis\sgood)?\)?/i", $scoring_description, $matches)) {
            $play = new Play(Play::KICK, self::$possessing_team, self::$defending_team);
            $play->setScoringMethod(Play::XP);
            $play->setOrigin($matches[0]);
            $play->setAuthor(self::makePlayer($matches[1], self::$possessing_team->getId() . "/" . $matches[1]));
            return $play;
        }
        
        // One-point conversion failed
        if (preg_match("/\(?(" . self::NAME_PATTERN_2W .")\sPAT\sfailed\)?/i", $scoring_description, $matches)) {     
            $play = new Play(Play::KICK, self::$possessing_team, self::$defending_team);
            $play->setScoringMethod(Play::XP);
            $play->setOrigin($matches[0]);
            $play->setAuthor(self::makePlayer($matches[1], self::$possessing_team->getId() . "/" . $matches[1]));
            $play->setResult(false);
            return $play;
        }
        
        // Two-point conversion failed
        if (preg_match("/\(?(two-point\s(pass|run)?\s?conversion\sfailed)\)?/i", $scoring_description, $matches)) {
            $play = new Play(Play::OTHER, self::$possessing_team, self::$defending_team);
            $play->setScoringMethod(Play::X2P);
            $play->setOrigin($matches[0]);
            $play->setResult(false);
            return $play;
        }
        
        // Two-point pass conversion with brackets
        $pattern = "\((" . self::NAME_PATTERN_3W . ")\spass\sto\s";
        $pattern .= "(" . self::NAME_PATTERN_3W . ")\sfor\stwo-point\sconversion\)";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $play = new Play(Play::PASS, self::$possessing_team, self::$defending_team);
            $play->setScoringMethod(Play::X2P);
            $play->setOrigin($matches[0]);
            $play->setAuthor(self::makePlayer($matches[2], self::$possessing_team->getId() . "/" . $matches[2]));
            $play->setPasser(self::makePlayer($matches[1], self::$possessing_team->getId() . "/" . $matches[1]));
            return $play;
        }
        
        // Two-point run conversion with brackets
        $pattern = "\((" . self::NAME_PATTERN_3W . ")\srun\sfor\stwo-point\sconversion\)";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $play = new Play(Play::RUN, self::$possessing_team, self::$defending_team);
            $play->setScoringMethod(Play::X2P);
            $play->setOrigin($matches[0]);
            $play->setAuthor(self::makePlayer($matches[1], self::$possessing_team->getId() . "/" . $matches[1]));
            return $play;
        }
        
        // Two-point pass conversion with no brackets
        $pattern = "(" . self::NAME_PATTERN_2W . ")\spass\sto\s";
        $pattern .= "(" . self::NAME_PATTERN_3W . ")\sfor\stwo-point\sconversion";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $play = new Play(Play::PASS, self::$possessing_team, self::$defending_team);
            $play->setScoringMethod(Play::X2P);
            $play->setOrigin($matches[0]);
            $play->setAuthor(self::makePlayer($matches[2], self::$possessing_team->getId() . "/" . $matches[2]));
            $play->setPasser(self::makePlayer($matches[1], self::$possessing_team->getId() . "/" . $matches[1]));
            return $play;
        }
        
        // Two-point run conversion with no brackets
        $pattern = "(" . self::NAME_PATTERN_2W . ")\srun\sfor\stwo-point\sconversion";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $play = new Play(Play::RUN, self::$possessing_team, self::$defending_team);
            $play->setScoringMethod(Play::X2P);
            $play->setOrigin($matches[0]);
            $play->setAuthor(self::makePlayer($matches[1], self::$possessing_team->getId() . "/" . $matches[1]));
            return $play;
        }
        
        return null;
    }

    /**
     * Converts textual field goal description to instance of Robin\Play
     *
     * @param   string  $scoring_description    Full textual description of FG
     * @return  Play                            Instance of Robin\Play or null
     */
    public static function FG(string $scoring_description): ?Play
    {
        self::checkDefaultTeams();
        
        $pattern = "(" . self::NAME_PATTERN_2W . ")\s\d{1,2}\sya?r?ds?\sfield\sgoal";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $play = new Play(Play::KICK, self::$possessing_team, self::$defending_team);
            $play->setScoringMethod(Play::FG);
            $play->setOrigin($matches[0]);
            $play->setAuthor(self::makePlayer($matches[1], self::$possessing_team->getId() . "/" . $matches[1]));
            return $play;
        }
        
        return null;
    }

    /**
     * Converts textual safety description to instance of Robin\Play
     *
     * @param   string  $scoring_description    Full textual description of SF
     * @return  Play                            Instance of Robin\Play or null
     */
    public static function SF(string $scoring_description): ?Play
    {
        self::checkDefaultTeams();
    
        $play = new Play(Play::OTHER, self::$possessing_team, self::$defending_team);
        $play->setScoringMethod(Play::SF);
        $play->setOrigin($scoring_description);
        return $play;
    }

    /**
     * Rare case of converting textual defensive two-points description
     * to instance of Robin|Play
     *
     * @param   string  $scoring_description    Full textual description of D2P
     * @return  Play                            Instance of Robin\Play or null
     */
    public static function D2P(string $scoring_description): ?Play
    {
        self::checkDefaultTeams();
        
        $pattern = "(" . self::NAME_PATTERN_3W . ")\sdefensive\spat\sconversion";
        if (preg_match("/" . $pattern ."/i", $scoring_description, $matches)) {
            $play = new Play(Play::PAT_RETURN, self::$possessing_team, self::$defending_team);
            $play->setScoringMethod(Play::D2P);
            $play->setOrigin($matches[0]);
            $play->setAuthor(self::makePlayer($matches[1], self::$possessing_team->getId() . "/" . $matches[1]));
            return $play;
        }
        
        return null;
    }

    /**
     * Check if Decompose::$possessing_team and Decompose::$defending_team ara set
     * 
     * @return  void
     */
    private static function checkDefaultTeams(): void
    {
        if (!is_a(self::$possessing_team, "\Robin\Team")) {
            throw new Exception("Please, set default possessing team with Decompose::setPossessingTeam() before decomposing plays");
        }
        
        if (!is_a(self::$defending_team, "\Robin\Team")) {
            throw new Exception("Please, set default defending team with Decompose::setDefendingTeam() before decomposing plays");
        }
    }

    /**
     * Creates Player object from player name and player id (optionally)
     *
     * @param   string  $player_name    Name of the player, e.g. "Matt Ryan"
     * @param   string  $player_id      Id of the player, e.g. "Atlanta Falcons/Matt Ryan"
     * @return  Player                  Player object
     */
    private static function makePlayer(string $player_name, string $player_id = ""): Player
    {
        if (strlen($player_name) == 0) {
            throw new Exception("Player name cannot be empty");
        }
        
        $player = new Player($player_name);
        if (strlen($player_id) > 0) {
            $player->setId($player_id);
        }
        
        return $player;
    }

}