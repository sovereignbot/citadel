<?php
// Init the container
$container = new \Pimple\Container();

// Add dependencies here
$container["log"] = function($container) {
    $log = new \Monolog\Logger("Sovereign");
    $log->pushHandler(new \Monolog\Handler\StreamHandler("php://stdout", \Monolog\Logger::INFO));

    return $log;
};

$container["config"] = function($container) {
    return new \Sovereign\Lib\Config($container);
};

$container["db"] = function($container) {
    return new \Sovereign\Lib\Db($container["config"], $container["log"]);
};

$container["curl"] = function($container) {
    return new \Sovereign\Lib\cURL($container["log"]);
};

$container["settings"] = function($container) {
    return new \Sovereign\Lib\Settings($container["db"]);
};

$container["permissions"] = function($container) {
    return new \Sovereign\Lib\Permissions($container["db"]);
};

$container["users"] = function($container) {
    return new \Sovereign\Lib\Users($container["db"]);
};

$container["wolframAlpha"] = function($container) {
    $appID = $container["config"]->get("appID", "wolframalpha");
    return new WolframAlpha\Engine($appID);
};

$startTime = time();
$container["startTime"] = function($container) use ($startTime) {
    return $startTime;
};

// Keep at the bottom to return the container
return $container;