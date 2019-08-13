<?
	ob_start();
	
	include("includes/dom.php"); // Simple HTML DOM lib
	
	// Setting the defaults
	$log = array( );
	$url = "";
	$output = "";
	$error_status = FALSE; // if FALSE, everything is ok
	$error_message = "";
	$supported_domains = array("espn.com","www.espn.com","robin.local","robin.firstandgoal.in");
	
	// Setting the constants for play types
	define("PASS", 1);
	define("RUSH", 2);
	define("INTERCEPTION_RETURN", 3);
	define("FUMBLE_RETURN", 4);
	define("FUMBLE_RECOVERY", 5);
	define("PUNT_RETURN", 6);
	define("KICKOFF_RETURN", 7);
	define("KICK", 8);
	define("DEFENSE", 9);
	
	// Constants for scoring
	define("Q1", 1);
	define("Q2", 2);
	define("Q3", 3);
	define("Q4", 4);
	define("OT", 5);
	define("HOME_TEAM", 0);
	define("AWAY_TEAM", 1);
	define("TD", 1);
	define("FG", 2);
	define("SF", 3);
	define("XP", 4);
	define("D2P", 5);
	
	// Constants for translation with t()
	define("PLAYER_NAME", 10);
	define("FULL_PLAYER_NAME", 11);
	define("SHORT_PLAYER_NAME", 12);
	define("PLAYER_NAME_GENITIVE", 13);
	define("FULL_PLAYER_NAME_GENITIVE", 14);
	define("PLAYER_POSITION", 15);
	define("PLAYER_NUMBER", 16);
	define("TEAM_NAME", 20);
	define("SHORT_TEAM_NAME", 21);
	define("TEAM_IMAGE", 22);
	define("STATS", 30);
	
	$players = parse_ini_file("players.ini", 1);
	$teams = parse_ini_file("teams.ini", 1);
	
	function state($message, $line = FALSE)
	{
		global $log;
		
		if($line) $message .= ' (line: '.$line.')';
		
		$log[] = $message;
	}
	
	function c($string)
	{
		$string = str_replace("&nbsp;", " ", $string); // remove all unnecessary spaces
		$string = str_replace("&nbsp", " ", $string); // for some reason, &nbsp without ";" sometimes appear
		$string = preg_replace("~[^\w -]+~", "", $string); // All other non-word characters
		return trim($string);
	}
	
	function t($string, $type = PLAYER_NAME)
	{
		global $players, $teams;
		
		if($type == PLAYER_NAME)
		{
			if(isset($players[$string]["first_name"]) && isset($players[$string]["last_name"]))
			{
				$string = $players[$string]["first_name"].' '.$players[$string]["last_name"];
			}
		}
		elseif($type == PLAYER_NUMBER)
		{
			if(isset($players[$string]["number"]) && mb_strlen($players[$string]["number"]) > 0)
			{
				$string = $players[$string]["number"];
			}
			else
			{
				$string = '';
			}
		}
		elseif($type == PLAYER_POSITION)
		{
			if(isset($players[$string]["position"]) && mb_strlen($players[$string]["position"]) > 0)
			{
				$string = $players[$string]["position"];
			}
			else
			{
				$string = '';
			}
			
		}
		elseif($type == FULL_PLAYER_NAME)
		{
			$p = t($string, PLAYER_POSITION);
			$n = t($string, PLAYER_NUMBER);
			if(mb_strlen($p) > 0) $p .= ' ';
			if(mb_strlen($n) > 0) $n = ' (#'.$n.')';
			
			$string = $p.t($string, PLAYER_NAME).$n;			
		}
		elseif($type == SHORT_PLAYER_NAME)
		{
			if(isset($players[$string]["first_name"]) && isset($players[$string]["last_name"]))
			{
				$string = mb_substr($players[$string]["first_name"], 0, 1).'. '.$players[$string]["last_name"];
			}
			else
			{
				$string = mb_substr($string, 0, 1).'.'.mb_substr($string, mb_strpos($string, ' '));
			}	
		}
		elseif($type == PLAYER_NAME_GENITIVE)
		{
			if(isset($players[$string]["genitive"]))
			{
				$string = $players[$string]["genitive"];
			}
		}
		elseif($type == FULL_PLAYER_NAME_GENITIVE)
		{	
			$p = t($string, PLAYER_POSITION);
			$n = t($string, PLAYER_NUMBER);
			if(mb_strlen($p) > 0) $p .= ' ';
			if(mb_strlen($n) > 0) $n = ' (#'.$n.')';
			
			$string = $p.t($string, PLAYER_NAME_GENITIVE).$n;	
		}
		elseif($type == TEAM_NAME)
		{
			if(isset($teams[$string]["name"]))
			{
				$string = $teams[$string]["name"];
			}
		}
		elseif($type == SHORT_TEAM_NAME)
		{
			if(isset($teams[$string]["name"]))
			{
				$string = $teams[$string]["abbr"];
			}
			else
			{
				$string = mb_strtoupper(mb_substr($string, 0, 3));
			}
		}
		elseif($type == TEAM_IMAGE)
		{
			if(isset($teams[$string]["logo"]))
			{
				$string = $teams[$string]["logo"];
			}
			else
			{
				$string = NULL;
			}
		}
		
		return $string;
	}
	
	function getGameLeaders($e, $search_query) // Parsing stats leaders 
	{
		$ret = array( );
		$tag = $e->find($search_query, 0);
				
		if($tag != null) //if tag by search_query is found
		{
			$name = $tag->getAttribute("title"); //taking the name from the attribute
			$ret["name"] = (isset($name)) ? c($name) : '';

			$tag = $tag->parent()->find(".player-stats", 0); //taking the stats from the sibling tag
			$ret["stat"] = ($tag != null) ? c($tag->plaintext) : '';
		}
		else state("No game leader was found by query `".$search_query."`");
		
		return $ret;
	}
	
	
	// Put touchdown description string into serialized array
	function decomposeTD($desc)
	{
		$result = array( );
		$lower_desc = mb_strtolower($desc);
		
		if(mb_strpos($lower_desc, "yd pass from") !== FALSE || mb_strpos($lower_desc, "pass to") !== FALSE)
		{
			// It was a pass play & there are two dufferent ways to describe it
			if(preg_match('/(\d{1,3} yd pass from)/', $lower_desc, $pattern))
			{
				$result['type'] = PASS;
				$result['player'] = trim(mb_substr($desc, 0, mb_stripos($lower_desc, $pattern[0])));
				$result['passer'] = trim(mb_substr($desc, mb_stripos($lower_desc, $pattern[0]) + mb_strlen($pattern[0])));
				$result['kicker'] = FALSE;
			}
			elseif(preg_match('/(pass to)/', $lower_desc, $pattern))
			{
				$result['type'] = PASS;
				$result['passer'] = trim(mb_substr($desc, 0, mb_stripos($lower_desc, $pattern[0])));
				
				$two_point_postfix = " for Two-Point Conversion";
				$two_point_postfix_pos = mb_stripos($lower_desc, $two_point_postfix);
				if($two_point_postfix !== FALSE)
					$result['player'] = trim(mb_substr($desc, mb_stripos($lower_desc, $pattern[0]) + mb_strlen($pattern[0]), (-1)*mb_strlen($two_point_postfix)));
				else
					$result['player'] = trim(mb_substr($desc, mb_stripos($lower_desc, $pattern[0]) + mb_strlen($pattern[0])));
					
				$result['kicker'] = FALSE;
			}
			else state("Pass play description was not found", __LINE__);
		}
		else
		{
			// Array with play types and patterns
			$plays = array(
				RUSH => 'yd run', INTERCEPTION_RETURN => 'yd interception return', FUMBLE_RETURN => 'yd fumble return',
				FUMBLE_RECOVERY => 'yd fumble recovery', PUNT_RETURN => 'yd punt return', KICKOFF_RETURN => 'yd kickoff return'
			);
			
			foreach($plays as $play_key=>$pattern)
			{
				if(mb_strpos($lower_desc, $pattern) !== FALSE)
				{
					if(preg_match('/(\d{1,3} '.$pattern.')/', $lower_desc, $pattern))
					{
						$result['type'] = $play_key;
						$result['player'] = trim(mb_substr($desc, 0, mb_stripos($lower_desc, $pattern[0])));
						$result['passer'] = FALSE;
						$result['kicker'] = FALSE;
					}
					else state("Play with `".$pattern."` pattern description was not found", __LINE__);	
				}
			}
			
			if(count($result) == 0) state("Touchdown description was not found", __LINE__);
		}
		
		return $result;
	}
	
	// Put field goal description string into serialized array
	function decomposeFG($desc)
	{
		$result = array( );
		
		$lower_desc = mb_strtolower($desc);
		
		if(mb_strpos($lower_desc, "yd field goal") !== FALSE)
		{
			// It was a field goal
			if(preg_match('/(\d{1,3} yd field goal)/', $lower_desc, $pattern))
			{
				$result['type'] = KICK;
				$result['player'] = FALSE;
				$result['passer'] = FALSE;
				$result['kicker'] = trim(mb_substr($desc, 0, mb_stripos($lower_desc, $pattern[0])));
			}
			else state("Field goal description was not found", __LINE__);
			
		}
		else state("Field goal description was not found", __LINE__);
		
		return $result;
	}
	
	// Put extra point description into serialized array
	function decomposePAT($desc)
	{
		$result = array( );
		
		$lower_desc = mb_strtolower($desc);
		
		if($pos = mb_strpos($lower_desc, " kick")) //it's a field goal
		{
			$result['xp_type'] = KICK;
			$result['xp_player'] = FALSE;
			$result['xp_passer'] = FALSE;
			$result["xp_kicker"] = trim(mb_substr($desc, 0, $pos));
			$result["xp_failed"] = FALSE;
		}
		elseif($pos = mb_strpos($lower_desc, " pat failed")) // it's a failed kick
		{
			$result['xp_type'] = KICK;
			$result['xp_player'] = FALSE;
			$result['xp_passer'] = FALSE;
			$result["xp_kicker"] = FALSE;
			$result["xp_failed"] = TRUE;
		}
		else // it's a two point conversion
		{
			$two_points = decomposeTD($desc); // we use touchdown decomposition
			foreach($two_points as $k=>$v)
			{
				$key = 'xp_'.$k; // add xp_ prefix to keys, so it will be defined as extra point values
				$result[$key] = $v;
			}
			$result["xp_failed"] = FALSE;
		}
		
		return $result;
	}
	
	// Put serialized array into score description string
	function describeScore($score_details)
	{
		$result = '';
		
		$descriptions = array(
					RUSH => 'на выносе', INTERCEPTION_RETURN => 'на возврате перехвата', FUMBLE_RETURN => 'на возврате фамбла',
					FUMBLE_RECOVERY => 'на подборе фамбла', PUNT_RETURN => 'на возврате панта',
					KICKOFF_RETURN => 'на возврате начального удара', DEFENSE => 'в защите'
					);
		
		if(isset($score_details["type"]))
		{
			if($score_details["type"] == PASS) // it's a pass, so the description differs from other
			{
				$result .= t($score_details["player"], FULL_PLAYER_NAME).' на приеме от '.t($score_details["passer"], FULL_PLAYER_NAME_GENITIVE);
			}
			elseif($score_details["type"] == KICK) // it's a kick, so we just name the kicker
			{
				$result .= ''.t($score_details["kicker"], FULL_PLAYER_NAME);
			}
			else // all other types of play, incl rush, return, etc.
			{
				if(in_array($score_details["type"], array_flip($descriptions)))
				{
					$result .= t($score_details["player"], FULL_PLAYER_NAME).' '.$descriptions[$score_details["type"]];
				}	
				else state("Unknown scoring type", __LINE__);
			}
			
			$result .= describePAT($score_details); // add PAT description
		}
		else state("Invalid score details", __LINE__);
	
		return $result;
	}
	
	function describePAT($score_details)
	{
		$result = '';
		
		if(isset($score_details["xp_failed"]))
		{
			if($score_details["xp_failed"] == TRUE)
			{
				$result .= ' (x)';
			}
			else
			{
				if($score_details["xp_type"] == KICK)
				{
					$result .= ' (+1 '.t($score_details["xp_kicker"], FULL_PLAYER_NAME).')';
				}
				elseif($score_details["xp_type"] == PASS) // only pass and rush plays can be played here
				{
					$result .= ' (+2 '.t($score_details["xp_player"], FULL_PLAYER_NAME).' на приеме от '.t($score_details["xp_passer"], FULL_PLAYER_NAME_GENITIVE).')';
				}
				elseif($score_details["xp_type"] == RUSH)
				{
					$result .= ' (+2 '.t($score_details["xp_player"], FULL_PLAYER_NAME).' на выносе)';
				}
			}
		}
		
		return $result;
	}
	
	
/*	
	function describeTouchdown($desc, $is_xp = FALSE)
	{
		$result = array( );
		$player_key = "player";
		$passer_key = "passer";
		$type_key = "type";
		
		if($is_xp) //if it's extra point, we modify the keys of the return array
		{
			$player_key = 'xp_'.$player_key;
			$passer_key = 'xp_'.$passer_key;
			$type_key = 'xp_'.$type_key;
		}
		
		$pattern = array( );
		$lower_desc = mb_strtolower($desc);
		if(mb_strpos($lower_desc, "yd run") !== FALSE)
		{
			// It was a run play
			if(preg_match('/(\d{1,3} yd run)/', $lower_desc, $pattern))
			{
				$result[$player_key] = trim(mb_substr($desc,0,mb_stripos($lower_desc,$pattern[0])));
				$result[$type_key] = RUSH;
				return $result;
			}
			else state("Run game description was not found", __LINE__);
		}
		elseif(mb_strpos($lower_desc, "yd pass from") !== FALSE)
		{
			// It was a pass play
			if(preg_match('/(\d{1,3} yd pass from)/', $lower_desc, $pattern))
			{
				$result[$player_key] = trim(mb_substr($desc,0,mb_stripos($lower_desc,$pattern[0])));
				$result[$passer_key] = trim(mb_substr($desc,mb_stripos($lower_desc,$pattern[0])+mb_strlen($pattern[0])));
				$result[$type_key] = PASS;
				return $result;
			}
			else state("Pass game description was not found", __LINE__);
		}
		elseif(mb_strpos($lower_desc, "pass to") !== FALSE)
		{
			// It was a pass play, but described in other way
			if(preg_match('/(pass to)/', $lower_desc, $pattern))
			{
				$result[$passer_key] = trim(mb_substr($desc,0,mb_stripos($lower_desc,$pattern[0])));
				$result[$player_key] = trim(mb_substr($desc,mb_stripos($lower_desc,$pattern[0])+mb_strlen($pattern[0])));
				$result[$type_key] = PASS;
				return $result;
			}
			else state("Pass game description was not found", __LINE__);	
		}
		elseif(mb_strpos($lower_desc, "yd interception return") !== FALSE)
		{
			// Interception return
			if(preg_match('/(\d{1,3} yd interception return)/', $lower_desc, $pattern))
			{
				$result[$player_key] = trim(mb_substr($desc,0,mb_stripos($lower_desc,$pattern[0])));
				$result[$type_key] = INTERCEPTION_RETURN;
				return $result;
			}
			else state("Interception return description was not found", __LINE__);	
		}
		elseif(mb_strpos($lower_desc, "yd fumble return") !== FALSE)
		{
			// Fumble return
			if(preg_match('/(\d{1,3} yd fumble return)/', $lower_desc, $pattern))
			{
				$result[$player_key] = trim(mb_substr($desc,0,mb_stripos($lower_desc,$pattern[0])));
				$result[$type_key] = FUMBLE_RETURN;
				return $result;
			}
			else state("Fumble return description was not found", __LINE__);	
		}
		elseif(mb_strpos($lower_desc, "yd fumble recovery") !== FALSE)
		{
			// Fumble recovery
			if(preg_match('/(\d{1,3} yd fumble recovery)/', $lower_desc, $pattern))
			{
				$result[$player_key] = trim(mb_substr($desc,0,mb_stripos($lower_desc,$pattern[0])));
				$result[$type_key] = FUMBLE_RECOVERY;
				return $result;
			}
			else state("Fumble recovery description was not found", __LINE__);	
		}
		elseif(mb_strpos($lower_desc, "yd punt return") !== FALSE)
		{
			// Punt return
			if(preg_match('/(\d{1,3} yd punt return)/', $lower_desc, $pattern))
			{
				$result[$player_key] = trim(mb_substr($desc,0,mb_stripos($lower_desc,$pattern[0])));
				$result[$type_key] = PUNT_RETURN;
				return $result;
			}
			else state("Punt return description was not found", __LINE__);	
		}
		elseif(mb_strpos($lower_desc, "yd kickoff return") !== FALSE)
		{
			// Kick off return
			if(preg_match('/(\d{1,3} yd kickoff return)/', $lower_desc, $pattern))
			{
				$result[$player_key] = trim(mb_substr($desc,0,mb_stripos($lower_desc,$pattern[0])));
				$result[$type_key] = KICKOFF_RETURN;
				return $result;
			}
			else state("Kickoff return description was not found", __LINE__);	
		}
		
		return $result;
	}
*/
	
	if(count($_POST) > 0)
	{
		$url = isset($_POST["url"]) ? $_POST["url"] : "";
		
		if(filter_var($url, FILTER_VALIDATE_URL))
		{
			$domain = parse_url($url, PHP_URL_HOST);
			
			if(in_array($domain, $supported_domains)) // Finally, if the domain is supported
			{
				$html = file_get_html($url); // getting the source and parse via Simple HTML DOM
				
				// Let's start with identifying home and away teams
				
				$tag = $html->find("div.competitors div.home a.team-name .long-name", 0);
				if($tag != null)
				{
					$home_team = $tag->plaintext;
				}
				else
				{
					$home_team = '';
					state("No home team name was found", __LINE__);
				}
				
				$tag = $html->find("div.competitors div.away a.team-name .long-name", 0);
				if($tag != null)
				{
					$away_team = $tag->plaintext;
				}
				else
				{
					$away_team = '';
					state("No away team name was found", __LINE__);
				}
				
				// Ok, then game statistical leaders
				
				$game_leaders = array("home"=>array( ),"away"=>array( ));
				$e = $html->find("div[data-module=teamLeaders]",0);
				
				if($e == null) state("No team Leaders block was found", __LINE__);
				
				// Passing
				$game_leaders["pass"][HOME_TEAM] = getGameLeaders($e, "div[data-stat-key=passingYards] .home-leader .player-name");
				$game_leaders["pass"][AWAY_TEAM] = getGameLeaders($e, "div[data-stat-key=passingYards] .away-leader .player-name");
				
				// Rushing
				$game_leaders["rush"][HOME_TEAM] = getGameLeaders($e, "div[data-stat-key=rushingYards] .home-leader .player-name");
				$game_leaders["rush"][AWAY_TEAM] = getGameLeaders($e, "div[data-stat-key=rushingYards] .away-leader .player-name");
				
				// Rushing
				$game_leaders["reception"][HOME_TEAM] = getGameLeaders($e, "div[data-stat-key=receivingYards] .home-leader .player-name");
				$game_leaders["reception"][AWAY_TEAM] = getGameLeaders($e, "div[data-stat-key=receivingYards] .away-leader .player-name");				
				
				// Scoring summary
				
				$quarters = array(Q1=>array( ),Q2=>array( ),Q3=>array( ),Q4=>array( ),OT=>array( ),0=>array( ));
				$scores = array( );
				$current_score = array(HOME_TEAM=>0, AWAY_TEAM=>0);
				$current_quarter = Q1;
				
				/*
					For quarters scoring: for each quarter, 0 is home team, 1 for away, OT is shown if count(quarters[OT])==2, Total is 0
					For scoring summary, every play is an array where:
					array(
							"quarter" => , // Q1, Q2, Q3, Q4, OT -- see constants
							"scorer" => , // HOME_TEAM (0), AWAY_TEAM (1) -- see constants
							"method" => , // TD (1), FG (2), XP (3), SF (4) -- see constants
							"type" => , // See play types in constants
							"player" => , // Player name as in source
							"passer" => , // Passing player name as in source
							"kicker" => , // Kicking player name as in source
							"xp_type" => , // See play types in constants
							"xp_player" => , // Player name as in source
							"xp_passer" => , // Passing player name as in source
							"xp_kicker" => , // Kicking player name as in source
							"xp_failed" => , // TRUE/FALSE
							"home_score" => , // Home team score after the play
							"away_score" => // Home team score after the play
						);
				*/
				
				$scoring_summary = $html->find("div[data-module=scoringSummary] div.scoring-summary > table tbody",0);
				if($scoring_summary == null) state("No scoring summary block was found", __LINE__);
				
				$scoring_events = $scoring_summary->find("tr");
				
				foreach($scoring_events as $e)
				{
					if($e->getAttribute("class") == "highlight") // Checking if current row is the identyfier of the quarter
					{
						$quarter_header = $e->find("th.quarter",0);
						if($quarter_header != null)
						{
							$quarter_header = explode(" ",mb_strtolower(c($quarter_header->innertext)));
							
							if(count($quarter_header) == 2 && $quarter_header[1] == "quarter")
							{
								switch($quarter_header[0])
								{
									case 'first':
										$current_quarter = Q1;
										break;
									case 'second':
										$current_quarter = Q2;
										break;
									case 'third':
										$current_quarter = Q3;
										break;
									case 'fourth':
										$current_quarter = Q4;
										break;
									default:
										$current_quarter = Q1;
										break;
								}
							}
							else $current_quarter = OT; //if second word isn't "quarter", it's overtime
							
						}
						else state("No scoring summary block was found", __LINE__);
					}
					else
					{
						// Ok, it's scoring row
						$scoring_row = array("quarter" => $current_quarter);
						
						// Let's see, what have changed in scores
						$home_score_delta = 0;
						$new_home_score = $e->find("td.away-score",0); // in scoring summary on ESPN home and away columns are swipped
						if($new_home_score != null && is_numeric(trim($new_home_score->innertext)))
						{
							$home_score_delta = trim($new_home_score->innertext)-$current_score[HOME_TEAM];
							$scoring_row["home_score"] = trim($new_home_score->innertext);
							$current_score[HOME_TEAM] = trim($new_home_score->innertext);
						}
						else state("Home score parse error", __LINE__);
						
						$away_score_delta = 0;
						$new_away_score = $e->find("td.home-score",0); // in scoring summary on ESPN home and away columns are swipped
						if($new_away_score != null && is_numeric(trim($new_away_score->innertext)))
						{
							$away_score_delta = trim($new_away_score->innertext)-$current_score[AWAY_TEAM];
							$scoring_row["away_score"] = trim($new_away_score->innertext);
							$current_score[AWAY_TEAM] = trim($new_away_score->innertext);
						}
						else state("Away score parse error", __LINE__);
						
						// Checking which score it was
						if($home_score_delta > $away_score_delta)
						{
							$scoring_row["scorer"] = HOME_TEAM;
							$score_delta = $home_score_delta;
						}
						elseif($home_score_delta < $away_score_delta)
						{
							$scoring_row["scorer"] = AWAY_TEAM;
							$score_delta = $away_score_delta;
						}
						else state("Score deltas are equal, something is wrong", __LINE__);
						
						$scoring_description = $e->find("td.game-details div.table-row",0);
						
						// Trying to find method (TD, FG, etc) and type (pass, rush, etc) of scoring by parsing the scoring row
						if($scoring_description != null)
						{
							// Method of play
							$scoring_type = $scoring_description->find(".score-type",0);
							
							if($scoring_type != null)
							{
								switch($scoring_type->innertext)
								{
									case 'TD':
										$scoring_row["method"] = TD;
										break;
									case 'FG':
										$scoring_row["method"] = FG;
										break;
									case 'XP':
										$scoring_row["method"] = XP;
										break;
									case 'SF':
										$scoring_row["method"] = SF;
										break;
									case 'D2P':
										$scoring_row["method"] = D2P;
										break;
									default:
										state("Unusual method of scoring found", __LINE__);
										break;
								}
							}
							else state("No play details block was found", __LINE__);
							
							// Scoring desciption
							$scoring_description = $scoring_description->find("div.drives div.headline", 0);
							
							if($scoring_description != null)
							{
								$scoring_row['source'] = $scoring_description->innertext;
								
								if($scoring_row["method"] == TD)
								{
									$scoring_description = explode("(",$scoring_description->innertext); // splitting score and extra points
									$scoring_row = array_merge($scoring_row, decomposeTD($scoring_description[0]));
									
									if($score_delta == 6)
									{
										$scoring_row['xp_type'] = KICK;
										$scoring_row['xp_player'] = FALSE;
										$scoring_row['xp_passer'] = FALSE;
										$scoring_row["xp_kicker"] = FALSE;
									}
									elseif(isset($scoring_description[1]))
									{
										$scoring_row = array_merge($scoring_row, decomposePAT($scoring_description[1]));	
									}
									else state("Unexpected case in score description parsing", __LINE__);	
								}
								elseif($scoring_row["method"] == FG)
								{
									$scoring_row = array_merge($scoring_row, decomposeFG($scoring_description->innertext));
								}
								elseif($scoring_row["method"] == XP)
								{
									$scoring_row = array_merge($scoring_row, decomposePAT($scoring_description->innertext));	
								}
								elseif($scoring_row["method"] == SF)
								{
									$scoring_row['player'] = FALSE;
									$scoring_row['passer'] = FALSE;
									$scoring_row['kicker'] = FALSE;
									$scoring_row['play'] = DEFENSE;
								}
								elseif($scoring_row["method"] == D2P)
								{
									$pos = mb_stripos($scoring_description->innertext, "Defensive PAT Conversion");
									if($pos !== FALSE)
									{
										$scoring_row['player'] = trim(mb_substr($scoring_description->innertext, 0, $pos));
										$scoring_row['passer'] = FALSE;
										$scoring_row['kicker'] = FALSE;
										$scoring_row['play'] = DEFENSE;
									}
									else state("Unknown D2P description", __LINE__);	
								}
							}
						}
						else state("No scoring description block was found", __LINE__);
						$scores[] = $scoring_row;
					}
				}
				
				// Parsing the qurters score
				
				$table_score = $html->find("table#linescore tbody", 0);
				
				if($table_score != null)
				{
					$table_rows = $table_score->find("tr");
					
					if(count($table_rows) == 2)
					{	
						//home score
						$table_cells = $table_rows[1]->find("td");
						$q = 0;
						foreach($table_cells as $td)
						{
							$td_class = $td->getAttribute("class");
							if($td_class == "team-name")
							{
								//do nothing, skip
							}
							elseif($td_class == "final-score")
							{
								$quarters[0][HOME_TEAM] = $td->innertext();
							}
							else
							{
								$quarters[++$q][HOME_TEAM] = $td->innertext();
							}
						}
						
						//Away score
						$table_cells = $table_rows[0]->find("td");
						$q = 0;
						foreach($table_cells as $td)
						{
							$td_class = $td->getAttribute("class");
							if($td_class == "team-name")
							{
								//do nothing, skip
							}
							elseif($td_class == "final-score")
							{
								$quarters[0][AWAY_TEAM] = $td->innertext();
							}
							else
							{
								$quarters[++$q][AWAY_TEAM] = $td->innertext();
							}
						}
					}
					else state("Table with quarters score contains more then 2 rows", __LINE__);
				}
				else state("Table with quarters score not found", __LINE__);
				
				// Parsing is done, now we need to convert to F&G Table tag
				
				$result = '';
				
				// Header
				$result .= '[table class="table-score"]'.PHP_EOL;
				$result .= ','.t($home_team, TEAM_NAME).',<strong>';
				$result .= $quarters[0][HOME_TEAM].'–'.$quarters[0][HOME_TEAM];
				$result .= '</strong>,'.t($away_team, TEAM_NAME).','.PHP_EOL;
				$result .= '[/table]';
				
				$result .= PHP_EOL.PHP_EOL;
				
				// Score table
				$result .= '[table width="450"]'.PHP_EOL;
				$result .= ',1,2,3,4,';
				if(count($quarters[5])==2) $result .= 'OT,';
				$result .= 'Итог'.PHP_EOL;
				$result .= t($home_team, TEAM_NAME).',';
				for($i=1; $i<count($quarters); $i++)
				{
					if(isset($quarters[$i][HOME_TEAM])) $result .= $quarters[$i][HOME_TEAM].',';
				}
				$result .= '<strong>'.$quarters[0][HOME_TEAM].'</strong>'.PHP_EOL;
				$result .= t($away_team, TEAM_NAME).',';
				for($i=1; $i<count($quarters); $i++)
				{
					if(isset($quarters[$i][AWAY_TEAM])) $result .= $quarters[$i][AWAY_TEAM].',';
				}
				$result .= '<strong>'.$quarters[0][AWAY_TEAM].'</strong>'.PHP_EOL;
				$result .= '[/table]';
				
				$result .= PHP_EOL.PHP_EOL;
				
				// Game leaders
				$result .= '[table caption="Лидеры статистики"]'.PHP_EOL;
				$result .= 'Категория,'.t($home_team, TEAM_NAME).','.t($away_team, TEAM_NAME).PHP_EOL;
				$result .= 'Пас,';
				$result .= t($game_leaders["pass"][HOME_TEAM]["name"], SHORT_PLAYER_NAME).' – '.t($game_leaders["pass"][HOME_TEAM]["stat"], STATS).',';
				$result .= t($game_leaders["pass"][AWAY_TEAM]["name"], SHORT_PLAYER_NAME).' – '.t($game_leaders["pass"][AWAY_TEAM]["stat"], STATS).PHP_EOL;
				$result .= 'Вынос,';
				$result .= t($game_leaders["rush"][HOME_TEAM]["name"], SHORT_PLAYER_NAME).' – '.t($game_leaders["rush"][HOME_TEAM]["stat"], STATS).',';
				$result .= t($game_leaders["rush"][AWAY_TEAM]["name"], SHORT_PLAYER_NAME).' – '.t($game_leaders["rush"][AWAY_TEAM]["stat"], STATS).PHP_EOL;
				$result .= 'Прием,';
				$result .= t($game_leaders["reception"][HOME_TEAM]["name"], SHORT_PLAYER_NAME).' – '.t($game_leaders["reception"][HOME_TEAM]["stat"], STATS).',';
				$result .= t($game_leaders["reception"][AWAY_TEAM]["name"], SHORT_PLAYER_NAME).' – '.t($game_leaders["reception"][AWAY_TEAM]["stat"], STATS).PHP_EOL;
				$result .= '[/table]';
				
				$result .= PHP_EOL.PHP_EOL;;
				
				// Scoring summary
				$result .= '[table th="0" caption="Ход игры"]'.PHP_EOL;;
				foreach($scores as $score)
				{
					$result_row = '';
					
					if($score["quarter"] == 5)
					{
						$result .= 'OT,';
					}
					elseif(is_numeric($score["quarter"]))
					{
						$result .= 'Q'.$score["quarter"].',';
					}
					else
					{
						$result .= ',';
						state("Invalid quarter value", __LINE__);
					}
					
					switch($score["method"])
					{
						case TD:
							$result .= 'ТД,';
							break;
						case FG:
							$result .= 'ФГ,';
							break;
						case SF:
							$result .= 'СФ,';
							break;
						case XP:
							$result .= 'Рлз,';
							break;
						case D2P:
							$result .= 'Рлз,';
							break;
						default:
							$result .= ',';
							state("Invalid quarter value", __LINE__);
							break;	
					}
					
					if($score["scorer"] == HOME_TEAM)
					{
						$result .= '<strong>'.t($home_team, SHORT_TEAM_NAME).'</strong>,';
					}
					elseif($score["scorer"] == AWAY_TEAM)
					{
						$result .= '<strong>'.t($away_team, SHORT_TEAM_NAME).'</strong>,';
					}
					else
					{
						$result .= ',';
						state("Invalid scoring team value", __LINE__);
					}
					
					$result .= describeScore($score).',';
					
					$result .= $score["home_score"].':'.$score["away_score"].PHP_EOL;
				}
				
				$result .= '[/table]';
				
				print_r($result);
			}
			else
			{
				$error_status = TRUE;
				$error_message = "This domain is not supported for parsing";				
			}
		}
		else
		{
			$error_status = TRUE;
			$error_message = "This string is not a correct URL";
		}
	}
	
	$output = ob_get_contents();
	ob_end_clean();
?><html>
<head>
	<title>Robin the bot</title>
	<link rel="icon" type="image/x-icon" href="/favicon.ico" />
	<link rel="icon" type="image/png" sizes="512x512" href="/favicon.png" />
	<style type="text/css">
		body { font-family:sans-serif; font-size: 10pt; }
		#wrapper { margin:0 auto; width:100%; min-width:300px; max-width:800px; }
		#url { width:300px; }
		#url + button {	cursor:pointer; }
		pre.ob { background:#eee; border:#ddd 1px solid; padding:10px; border-radius:5px; overflow:scroll; }
	</style>
</head>
<body>
	<div id="wrapper">
		<form method="post">
			<label>
				ESPN Game URL:
				<input type="text" value="<?=htmlspecialchars($url)?>" name="url" id="url" />
				<button type="submit">Parse</button>
			</label>
		</form>
		<? if($error_status): ?>
		<hr>
		<h1>Error</h1>
		<p><?=$error_message?></p>
		<? endif; ?>
		<? if(count($log) > 0): ?>
		<hr>
		<h1>Warnings</h1>
		<ul><? foreach($log as $message) echo '<li>'.$message.'</li>'; ?></ul>
		<? endif; ?>
		<? if(mb_strlen($output) > 0): ?>
		<hr>
		<h1>Output</h1>
		<pre class="ob"><?=htmlspecialchars($output)?></pre>
		<? endif; ?>
	</div>
</body>
</html>