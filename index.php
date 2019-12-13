<?php

error_reporting(E_ALL);

require_once "vendor/autoload.php";

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\BrowserConsoleHandler;
use Robin\Templater;
use Robin\Page;

define("ROOT", __DIR__);

// Setting up fancy error reporting
$whoops = new \Whoops\Run;
$whoops->prependHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

// Setting up monolog
$logger = new Logger("log");
$logger->pushHandler(new BrowserConsoleHandler(Logger::DEBUG));

$templater = new Templater("templates");


$layout_values = [];

if(array_key_exists("url", $_GET)) {
    $layout_values["url"] = htmlspecialchars($_GET["url"]);
    $layout_values["body"] = "";
    
    try {
        $page = new Page($_GET["url"], "ru_RU");
        $methods = $page->getMethods();
        
        foreach ($methods as $method) {
            $layout_values["body"] .= $templater->make($method, $page->{$method}());
        }
    } catch (Exception $e) {
        $layout_values["body"] = $templater->make("error", [ $e->getMessage() ]);
    }
}

echo $templater->make("layout", $layout_values);