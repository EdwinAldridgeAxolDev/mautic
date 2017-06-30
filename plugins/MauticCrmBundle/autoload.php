<?php

spl_autoload_register(function ($class_name) {
    $parts = explode('\\', $class_name);
    $currentDir = __DIR__;
    if ($parts[1] == 'MauticCrmBundle') {
        include $parts[2].'\\'.$parts[3].'.php';
    } elseif ($parts[1] == 'PluginBundle') {
        include "$currentDir\\..\\..\\app\\bundles\\$parts[1]\\$parts[2]\\$parts[3].php";
    }
//    include $class_name . '.php';
});

use MauticPlugin\MauticCrmBundle\Api\OdooApi;
use MauticPlugin\MauticCrmBundle\Integration\OdooIntegration;

$obj  = new OdooIntegration();
$obj2 = new OdooApi($obj);
