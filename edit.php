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
    
    if (array_key_exists("password", $_COOKIE) && strlen($_COOKIE["password"]) > 0){
        $layout_values["password"] = htmlspecialchars($_COOKIE["password"]);
    }
    
    $fh = new FileHandler("data");
    $object_id = $_GET["category"] . "/" . $_GET["id"];

    $layout_values["attributes"] = explode(",", $_GET["attributes"]);
    
    if (count($_POST) > 0) {
        
        if (array_key_exists("referer", $_POST) && strlen($_POST["referer"]) > 0) {
            $layout_values["referer"] = $_POST["referer"];
        }
                
        if (!array_key_exists("values", $_POST) || !is_array($_POST["values"])) {
            throw new Exception("Не задано содержание настройки объекта");
        }
        
        if (!array_key_exists("password", $_POST)) {
            throw new Exception("Введите пароль");
        }
        
        if ($_POST["password"] == "sovaf2010") {
            setcookie("password", $_POST["password"], time()+86400*31);
        } else {
            throw new Exception("Неверный пароль");
        } 
        
        $layout_values["locales"] = array_keys($_POST["values"]);
        $layout_values["values"] = $_POST["values"];
        
        // HERE WE NEED TO VERIFY IF $_POST["values"] IS VALUD SET OF VALUES
        
        if ($fh->save($object_id, $_POST["values"])) {
            $layout_values["result"] = $templater->make("success", [ "Настройка сохранена" ]);
        } else {
            $layout_values["result"] = $templater->make("error", [ "Ошибка при сохранении" ]);
        }
    } else {
        if (!array_key_exists("attributes", $_GET)) {
            throw new Exception("Не заданы атрибуты объекта");
        }
        $values = $fh->read($object_id);

        // If we successfully read values from saved file, we just pass it to template
        if (is_array($values)) {
            // Taking locales to template as columns
            $layout_values["locales"] = array_keys($values);
        } else {
            if (!array_key_exists("language", $_GET)) {
                throw new Exception("Не задан оригинальный язык объекта");
            }
            
            // If no data for this essence was stored in file, we take values
            // from $_GET, incl. locales, combined from $_GET["language"] and $_GET["locale"]
            $layout_values["locales"] = [ $_GET["language"] ];
            
            if (array_key_exists("locale", $_GET) && $_GET["language"] != $_GET["locale"]) {
                $layout_values["locales"][] = htmlspecialchars($_GET["locale"]);
            }
            
            // Putting values into similiar array of data as we retrive from file
            $language = htmlspecialchars($_GET["language"]);
            foreach ($layout_values["attributes"] as $attribute) {
                if (array_key_exists($attribute, $_GET)) {
                    $values[$language][$attribute] = $_GET[$attribute];
                }
            }
        }
        
        $layout_values["values"] = $values;
    }
} catch (Exception $e) {
    $layout_values["result"] = $templater->make("error", [ $e->getMessage() ]);
}    

echo $templater->make("edit", $layout_values);