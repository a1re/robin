<?php
require_once "vendor/autoload.php";

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\BrowserConsoleHandler;

use Robin\Parser;

define("ROOT", __DIR__);

// Setting up fancy error reporting
$whoops = new \Whoops\Run;
$whoops->prependHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

// Setting up monolog
$logger = new Logger('log');
$logger->pushHandler(new BrowserConsoleHandler(Logger::DEBUG));

function get_values(string $type, string $url, string $lang)
{
    $protocol = (array_key_exists("HTTPS", $_SERVER) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $domain = $protocol . "://" .$_SERVER["HTTP_HOST"] . "/";
    $request_url = $domain . "data.php?get=" . $type . "&lang=" . $lang . "&url=" . $url;
    
    $result = file_get_contents($request_url);
    
    if ($result) {
        $json_array = json_decode($result, true);
        
        if (is_array($json_array) && count($json_array) > 0) {
            return $json_array;
        } else {
            return ["status" => ["code" => "400", "message" => "Unable to load JSON"]];
        }
    } else {
        return ["status" => ["code" => "400", "message" => "Unable to load JSON"]];
    }
}

$result = "";
if (count($_POST) > 0) {
	$url = isset($_POST["url"]) ? $_POST["url"] : "";
	
	$req = get_values("type", $url, "ru");
	
	if ($req["status"]["code"] == 200) {
    	if ($req["response"]["type"] == "Gamecast") {
            $req = get_values("gamecast/header", $url, "ru");
            
            if ($req["status"]["code"] == 200) {
                $home_team = $req["response"]["home_team"]["short_name"];
                $away_team = $req["response"]["away_team"]["short_name"];
                
                $code  = "[table class=\"table-score\"]" . PHP_EOL;
                $result .= "<h1>";
                
                if ($req["response"]["home_team"]["img"]) {
                    $code .= "<img class=\"alignnone size-thumbnail\" src=\"" . $req["response"]["home_team"]["img"] . "\" width=\"75\" height=\"75\" />";
                    $result .= "<img width=\"20\" height=\"20\" src=\"" . $req["response"]["home_team"]["img"] ."\" /> ";
                }
                $result .= $req["response"]["home_team"]["short_name"];
                $code .= "," . $req["response"]["home_team"]["short_name"] .",";
                
                if ($req["response"]["has_score"]) {
                    $code .= "<strong>" . $req["response"]["home_score"] . "&hyphen;" . $req["response"]["away_score"] . "</strong>";
                    $result .= " " . $req["response"]["home_score"] . "&hyphen;" . $req["response"]["away_score"] . " ";
                } else {
                    if ($req["response"]["time"]) {
                        $code .= "<strong>" . $req["response"]["time"] . "</strong>";
                    } else {
                        $code .= "<strong> – </strong>";
                    }
                    $result .= " – ";
                }
                
                $code .= "," . $req["response"]["away_team"]["short_name"] .",";
                $result .= $req["response"]["away_team"]["short_name"];
                if ($req["response"]["away_team"]["img"]) {
                    $code .= "<img class=\"alignnone size-thumbnail\" src=\"" . $req["response"]["away_team"]["img"] . "\" width=\"75\" height=\"75\" />";
                    $result .= " <img width=\"20\" height=\"20\" src=\"" . $req["response"]["away_team"]["img"] ."\" />";
                }
                $code .= PHP_EOL . "[/table]";
                
                if ($req["response"]["date"] && $req["response"]["time"]) {
                    $result .= " (" . $req["response"]["date"] . " " . $req["response"]["time"] . ")";
                }
                
                $result .= "</h1>" . PHP_EOL . "<pre class=\"ob\">" . htmlspecialchars($code) . "</pre>" . PHP_EOL;
            }
            
            $req = get_values("gamecast/quarters", $url, "ru");
            
            if ($req["status"]["code"] == 200) {
                if ($req["response"]["has_score"]) {
                    $result .= "<h2>Счет по четвертям</h2>" . PHP_EOL;
                    
                    $code = "[table width=\"450\"]" . PHP_EOL;
                    $result .= "<table>" . PHP_EOL;
                    
                    $code .= ",1,2,3,4";
                    $result .= "<tr><th> </th><th>1</th><th>2</th><th>3</th><th>4</th>";
                    if (array_key_exists("OT", $req["response"]["home"]["quarters"])) {
                        $code .= ",OT";
                        $result .= "<th>OT</th>";
                    }
                    $code .= ",Итог" . PHP_EOL;
                    $result .= "<th>Итог</th></tr>" . PHP_EOL;
                    
                    $code .= $req["response"]["home"]["team"]["short_name"] . ",";
                    $result .= "<tr><td>" . $req["response"]["home"]["team"]["short_name"] . "</td>";
                    
                    $code .= $req["response"]["home"]["quarters"]["Q1"] . ",";
                    $result .= "<td>" . $req["response"]["home"]["quarters"]["Q1"] . "</td>";
                    $code .= $req["response"]["home"]["quarters"]["Q2"] . ",";
                    $result .= "<td>" . $req["response"]["home"]["quarters"]["Q2"] . "</td>";
                    $code .= $req["response"]["home"]["quarters"]["Q3"] . ",";
                    $result .= "<td>" . $req["response"]["home"]["quarters"]["Q3"] . "</td>";
                    $code .= $req["response"]["home"]["quarters"]["Q4"] . ",";
                    $result .= "<td>" . $req["response"]["home"]["quarters"]["Q4"] . "</td>";
                    
                    if (array_key_exists("OT", $req["response"]["home"]["quarters"])) {
                        $code .= $req["response"]["home"]["quarters"]["OT"] . ",";
                        $result .= "<td>" . $req["response"]["home"]["quarters"]["OT"] . "</td>";
                    }
                    
                    $code .= $req["response"]["home"]["score"] . PHP_EOL;
                    $result .= "<td>" . $req["response"]["home"]["score"] . "</td></tr>" . PHP_EOL;
                    
                    $code .= $req["response"]["away"]["team"]["short_name"] . ",";
                    $result .= "<tr><td>" . $req["response"]["away"]["team"]["short_name"] . "</td>";
                    
                    $code .= $req["response"]["away"]["quarters"]["Q1"] . ",";
                    $result .= "<td>" . $req["response"]["away"]["quarters"]["Q1"] . "</td>";
                    $code .= $req["response"]["away"]["quarters"]["Q2"] . ",";
                    $result .= "<td>" . $req["response"]["away"]["quarters"]["Q2"] . "</td>";
                    $code .= $req["response"]["away"]["quarters"]["Q3"] . ",";
                    $result .= "<td>" . $req["response"]["away"]["quarters"]["Q3"] . "</td>";
                    $code .= $req["response"]["away"]["quarters"]["Q4"] . ",";
                    $result .= "<td>" . $req["response"]["away"]["quarters"]["Q4"] . "</td>";
                    
                    if (array_key_exists("OT", $req["response"]["away"]["quarters"])) {
                        $code .= $req["response"]["away"]["quarters"]["OT"] . ",";
                        $result .= "<td>" . $req["response"]["away"]["quarters"]["OT"] . "</td>";
                    }
                    
                    $code .= $req["response"]["away"]["score"] . PHP_EOL;
                    $result .= "<td>" . $req["response"]["away"]["score"] . "</td></tr>" . PHP_EOL;
                    
                    $code .= "[/table]";
                    $result .= "</table>" . PHP_EOL . "<pre class=\"ob\">" . htmlspecialchars($code) . "</pre>" . PHP_EOL;
                    
                }
            }
            
            $req = get_values("gamecast/leaders", $url, "ru");
            
            if ($req["status"]["code"] == 200) {
                $result .= "<h2>Лидеры статистики</h2>" . PHP_EOL;
                
                $code = "[table caption=\"Лидеры статистики\"]" . PHP_EOL;
                $result .= "<table>" . PHP_EOL . "<tr>";
                
                $code .= "Категория,";
                $result .= "<th>Категория</th>";
                
                if (isset($home_team)) {
                    $code .= $home_team . ",";
                    $result .= "<th>" . $home_team . "</th>";
                } else {
                    $code .= "Хозяева,";
                    $result .= "<th>Хозяева</th>";
                }
                
                if (isset($away_team)) {
                    $code .= $away_team;
                    $result .= "<th>" . $away_team . "</th>";
                } else {
                    $code .= "Гости";
                    $result .= "<th>Гости</th>";
                }
                $code .= PHP_EOL;
                $result .= "</tr>" . PHP_EOL;
                
                $code .= "Пас,";
                $result .= "<tr><td>Пас</td>";
                
                $fname_short = mb_substr($req["response"]["home_team"]["passing"]["player"]["first_name"], 0, 1) . ". ";
                $fname_long = $req["response"]["home_team"]["passing"]["player"]["first_name"] . " ";
                $stat = $req["response"]["home_team"]["passing"]["player"]["last_name"] . " – ";
                $stat .= implode(", ", $req["response"]["home_team"]["passing"]["stats"]);

                $code .= str_replace(",", "\,", $fname_short . $stat) . ",";
                $result .= "<td>" . $fname_long . $stat . "</td>";
                
                $fname_short = mb_substr($req["response"]["away_team"]["passing"]["player"]["first_name"], 0, 1) . ". ";
                $fname_long = $req["response"]["away_team"]["passing"]["player"]["first_name"] . " ";
                $stat = $req["response"]["away_team"]["passing"]["player"]["last_name"] . " – ";
                $stat .= implode(", ", $req["response"]["away_team"]["passing"]["stats"]);

                $code .= str_replace(",", "\,", $fname_short . $stat) . PHP_EOL;
                $result .= "<td>" . $fname_long . $stat . "</td></tr>" . PHP_EOL;
                
                $code .= "Вынос,";
                $result .= "<tr><td>Вынос</td>";
                
                $fname_short = mb_substr($req["response"]["home_team"]["rushing"]["player"]["first_name"], 0, 1) . ". ";
                $fname_long = $req["response"]["home_team"]["rushing"]["player"]["first_name"] . " ";
                $stat = $req["response"]["home_team"]["rushing"]["player"]["last_name"] . " – ";
                $stat .= implode(", ", $req["response"]["home_team"]["rushing"]["stats"]);

                $code .= str_replace(",", "\,", $fname_short . $stat) . ",";
                $result .= "<td>" . $fname_long . $stat . "</td>";
                
                $fname_short = mb_substr($req["response"]["away_team"]["rushing"]["player"]["first_name"], 0, 1) . ". ";
                $fname_long = $req["response"]["away_team"]["rushing"]["player"]["first_name"] . " ";
                $stat = $req["response"]["away_team"]["rushing"]["player"]["last_name"] . " – ";
                $stat .= implode(", ", $req["response"]["away_team"]["rushing"]["stats"]);

                $code .= str_replace(",", "\,", $fname_short . $stat) . PHP_EOL;
                $result .= "<td>" . $fname_long . $stat . "</td></tr>" . PHP_EOL;
                
                $code .= "Прием,";
                $result .= "<tr><td>Прием</td>";
                
                $fname_short = mb_substr($req["response"]["home_team"]["receiving"]["player"]["first_name"], 0, 1) . ". ";
                $fname_long = $req["response"]["home_team"]["receiving"]["player"]["first_name"] . " ";
                $stat = $req["response"]["home_team"]["receiving"]["player"]["last_name"] . " – ";
                $stat .= implode(", ", $req["response"]["home_team"]["receiving"]["stats"]);

                $code .= str_replace(",", "\,", $fname_short . $stat) . ",";
                $result .= "<td>" . $fname_long . $stat . "</td>";
                
                $fname_short = mb_substr($req["response"]["away_team"]["receiving"]["player"]["first_name"], 0, 1) . ". ";
                $fname_long = $req["response"]["away_team"]["receiving"]["player"]["first_name"] . " ";
                $stat = $req["response"]["away_team"]["receiving"]["player"]["last_name"] . " – ";
                $stat .= implode(", ", $req["response"]["away_team"]["receiving"]["stats"]);

                $code .= str_replace(",", "\,", $fname_short . $stat) . PHP_EOL;
                $result .= "<td>" . $fname_long . $stat . "</td></tr>" . PHP_EOL;
                
                $code .= "[/table]" . PHP_EOL;
                $result .= "</table>" . PHP_EOL . "<pre class=\"ob\">" . htmlspecialchars($code) . "</pre>" . PHP_EOL;
                
            }
            
            $req = get_values("gamecast/events", $url, "ru");
            
            if ($req["status"]["code"] == 200) {
                $result .= "<h2>Ход игры</h2>" . PHP_EOL;
                
                $code = "[table caption=\"Ход игры\" th=\"0\"]" . PHP_EOL;
                $result .= "<table>" . PHP_EOL;
                
                for ($i=0; $i<count($req["response"]); $i++) {
                    $score = [
                        $req["response"][$i]["quarter"],
                        $req["response"][$i]["method"],
                        "<strong>" . $req["response"][$i]["team"]["abbr"] . "</strong>"
                    ];
                    
                    $description = "";
                    
                    if ($req["response"][$i]["author"]["position"]) {
                        $description .= $req["response"][$i]["author"]["position"] . " ";
                    }
                    
                    $description .= $req["response"][$i]["author"]["first_name"] . " " . $req["response"][$i]["author"]["last_name"];

                    if ($req["response"][$i]["author"]["number"]) {
                        $description .= " (#" . $req["response"][$i]["author"]["number"] . ") ";
                    }
                    
                    $description .= " " . $req["response"][$i]["description"];
                    
                    if (array_key_exists("passer", $req["response"][$i])) {
                        if ($req["response"][$i]["passer"]["position"]) {
                            $description .= " ". $req["response"][$i]["passer"]["position"];
                        }
                        
                        if (mb_strlen($req["response"][$i]["passer"]["first_name_genitive"]) > 0) {
                            $description .= " " . $req["response"][$i]["passer"]["first_name_genitive"];
                        } else {
                            $description .= " " . $req["response"][$i]["passer"]["first_name"];
                        }
                        
                        if (mb_strlen($req["response"][$i]["passer"]["last_name_genitive"]) > 0) {
                            $description .= " " . $req["response"][$i]["passer"]["last_name_genitive"];
                        } else {
                            $description .= " " . $req["response"][$i]["passer"]["last_name"];
                        }

                        if ($req["response"][$i]["passer"]["number"]) {
                            $description .= " (#" . $req["response"][$i]["passer"]["number"] . ") ";
                        }
                    }
                    
                    if (array_key_exists($i+1, $req["response"]) && $req["response"][$i+1]["is_extra"] > 0) {
                        if ($req["response"][$i+1]["is_good"] == true) {
                            $xp_description = " (+" . $req["response"][$i+1]["is_extra"] . " ";
                            
                            if ($req["response"][$i+1]["author"]["position"]) {
                                $xp_description .= $req["response"][$i+1]["author"]["position"] . " ";
                            }
                            
                            $xp_description .= $req["response"][$i+1]["author"]["first_name"] . " " . $req["response"][$i+1]["author"]["last_name"];
        
                            if ($req["response"][$i+1]["author"]["number"]) {
                                $xp_description .= " (#" . $req["response"][$i+1]["author"]["number"] . ") ";
                            }
                            
                            $xp_description .= " " . $req["response"][$i+1]["description"];
                            
                            if (array_key_exists("passer", $req["response"][$i+1])) {
                                if ($req["response"][$i+1]["passer"]["position"]) {
                                    $xp_description .= " ". $req["response"][$i+1]["passer"]["position"];
                                }
                                
                                if (mb_strlen($req["response"][$i+1]["passer"]["first_name_genitive"]) > 0) {
                                    $xp_description .= " " . $req["response"][$i+1]["passer"]["first_name_genitive"];
                                } else {
                                    $xp_description .= " " . $req["response"][$i+1]["passer"]["first_name"];
                                }
                                
                                if (mb_strlen($req["response"][$i+1]["passer"]["last_name_genitive"]) > 0) {
                                    $xp_description .= " " . $req["response"][$i+1]["passer"]["last_name_genitive"];
                                } else {
                                    $xp_description .= " " . $req["response"][$i+1]["passer"]["last_name"];
                                }
        
                                if ($req["response"][$i+1]["passer"]["number"]) {
                                    $xp_description .= " (#" . $req["response"][$i+1]["passer"]["number"] . ") ";
                                }
                            }

                            
                            $description .= rtrim($xp_description) . ")";
                        } else {
                            $description .= " (x)";
                        }
                        $i++;
                    }
                    
                    $score[] = trim($description);
                    $score[] = $req["response"][$i]["home_score"] . ":" . $req["response"][$i]["away_score"];
                    
                    $code .= implode(",", $score). PHP_EOL;
                    $result .= "<tr><td>" . implode("</td><td>", $score) . "</td></tr>" . PHP_EOL;
                }
                
                $code .= "[/table]" . PHP_EOL;
                $result .= "</table>" . PHP_EOL . "<pre class=\"ob\">" . htmlspecialchars($code) . "</pre>" . PHP_EOL;
                
            }
    	}
	} else {
    	$result =  "<h1>" . $req["status"]["code"] . "</h1><p>" . $req["status"]["message"] ."</p>";
	}

} else {
    $url = "";
}
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
		td, th { padding: 5px 10px; border:#ddd 1px solid; }
		th { text-align: left; }
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
		<?php if(mb_strlen($result) > 0) echo $result; ?>
	</div>
</body>
</html>