<?php
header("Content-type:text/html;charset=utf-8");
date_default_timezone_set("Asia/Shanghai");

//application run time
define('GUI_RUN_TIME',time());

define('BASE_PATH',str_replace('\\','/',realpath(dirname(__FILE__).'/'))."/");

//api server namespace
define('API_NAMESPACE','App\Http\Api');

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    }
});

//autoload
require '../vendor/autoload.php';

$app = \App\Utils\Widgets\App::getInstance();

//api route is backend application -> commands
$app->group('/api', function () use ($app) {

    $request = $app->getContainer()->get('request');

    $path = $request->getUri()->getPath();

    $response = $app->getContainer()->get('response');

    //path array
    $pathArray = explode('/',$path);

    //unset path array
    unset($pathArray[0],$pathArray[1]);

    //reset path array index
    $pathArray = array_values($pathArray);

    $api = '/';
    $class = API_NAMESPACE.'\\';
    foreach ($pathArray as $v) {
        $api .= $v.'/';
        $class .= ucfirst($v).'\\';
    }
    $api = substr($api,0,strlen($api)-1);
    $class = substr($class,0,strlen($class)-1);
    if(!class_exists($class)) {
        return $response->withJson([
            'code' => 400,
            'msg'  => 'are you lost.'
        ],400);
    } else {
        $app->any($api,$class.":run");
    }
});
$app->run();


