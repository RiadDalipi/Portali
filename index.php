<?php
session_start();
require_once('vendor/autoload.php');
require_once('App/Helpers/functions.php');

use alkimisti\simplerouter\Router;

$router = new Router();
$router->resolve();