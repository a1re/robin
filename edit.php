<?php

error_reporting(E_ALL);

require_once "vendor/autoload.php";

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\BrowserConsoleHandler;
use Robin\Templater;
use Robin\Keeper;
use Robin\FileHandler;

define("ROOT", __DIR__);

// Setting up fancy error reporting
$whoops = new \Whoops\Run;
$whoops->prependHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

// Setting up monolog
$logger = new Logger("log");
$logger->pushHandler(new BrowserConsoleHandler(Logger::DEBUG));

$layout_values = [];

$templater = new Templater("templates");

try {  
    if (count($_GET) == 0) {
        $msg = "Прямой доступ к редактору лишен смысла. ";
        $msg .= "Зайдите по ссылке через <a href=\"/\">результат парсинга</a>.";
        throw new Exception($message);
    }
    
    if (!array_key_exists("id", $_GET)) {
        throw new Exception("Не задан идентификтор объекта");
    }

    if (!array_key_exists("category", $_GET)) {
        throw new Exception("Не задан тип объекта");
    }

    if (mb_strpos($_GET["category"], "/") !== false) {
        throw new Exception("Некорректный тип объекта");
    }

    if (mb_strpos($_GET["id"], "../") !== false) {
        throw new Exception("Некорректный идентификтор объекта");
    } else {
        $layout_values["id"] = htmlspecialchars($_GET["id"]);
    }
    
    $fh = new FileHandler("data");
    $object_id = $_GET["category"] . "/" . $_GET["id"] . ".ini";
    
    if (count($_POST) > 0) {
        
        if (array_key_exists("referer", $_POST) && strlen($_POST["referer"]) > 0) {
            $layout_values["referer"] = $_POST["referer"];
        }
                
        if (!array_key_exists("content", $_POST)) {
            throw new Exception("Не задано содержание настройки объекта");
        }
        
        if (strlen($_POST["content"]) == 0) {
            throw new Exception("Пустое содержание настройки объекта");
        }
        
        $layout_values["content"] = htmlspecialchars($_POST["content"]);
        
        if (!array_key_exists("password", $_POST)) {
            throw new Exception("Введите пароль");
        }
        
        if ($_POST["password"] != "sovaf2010") {
            throw new Exception("Неверный пароль");
        }
        
        $layout_values["password"] = htmlspecialchars($_POST["password"]);
        
        if (count(parse_ini_string($_POST["content"], true)) == 0) {
            $msg =  "Настройка должна быть валидным ";
            $msg .= "<a href=\"https://ru.wikipedia.org/wiki/.ini\" target=\"_blank\">файлом конфигурации .ini</a>.";
            throw new Exception($msg);
        }
        
        if ($fh->saveSource($object_id, $_POST["content"])) {
            $layout_values["result"] = $templater->make("success", [ "Настройка сохранена" ]);
        } else {
            $layout_values["result"] = $templater->make("error", [ "Ошибка при сохранении" ]);
        }
    } else {
        if ($file = $fh->readSource($object_id)) {
            $layout_values["content"] = htmlspecialchars($file);
        } else {
            if (!array_key_exists("language", $_GET)) {
                throw new Exception("Не задан оригинальный язык объекта");
            }
            
            if (!array_key_exists("attributes", $_GET)) {
                throw new Exception("Не заданы атрибуты объекта");
            }
            
            $ini = "[" . $_GET["language"] . "]" . PHP_EOL;
            $attributes = explode(",", $_GET["attributes"]);
            foreach ($attributes as $attribute) {
                $ini .= $attribute . " = \"";
                if (array_key_exists($attribute, $_GET)) {
                    $ini .= $_GET[$attribute];
                }
                $ini .= "\";" . PHP_EOL;
            }
            
            if (array_key_exists("locale", $_GET) && $_GET["language"] != $_GET["locale"]) {
                $ini .= PHP_EOL . "[" . $_GET["locale"] . "]" . PHP_EOL;
                foreach ($attributes as $attribute) {
                    $ini .= $attribute . " = \" \";" . PHP_EOL;
                }
            }
            
            $layout_values["content"] = htmlspecialchars($ini);
        }
    }
} catch (Exception $e) {
    $layout_values["result"] = $templater->make("error", [ $e->getMessage() ]);
}    

echo $templater->make("edit", $layout_values);

/*
do {
    if (!array_key_exists("id", $layout_values)) {

    }
    
    if (!array_key_exists("type", $layout_values)) {
        $layout_values["result"] = "Не задан тип объекта";
        $layout_values["result_type"] = "error";
        break;
    }
    
} while(1);
*/


/*



<html>
<head>
    <title>Robin the bot</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico" />
    <link rel="icon" type="image/png" sizes="512x512" href="/favicon.png" />
    <style type="text/css">
        body { font-family:sans-serif; font-size: 10pt; }
        #wrapper { margin:0 auto; width:100%; min-width:300px; max-width:800px; }
        textarea { width:100%; height:300px; background:#eee; font-family:'Courier New', monospace; font-weight:400; padding:5px; font-size:1em; border:#ddd 1px solid; border-radius:5px; }
    </style>
</head>
<body>
    <div id="wrapper">
        <h1><?=htmlspecialchars($_GET["id"]);?></h1>
        <?

        ?>
        <textarea><?=htmlspecialchars($ini);?></textarea>
    </div>
</body>
</html>
*/
?>