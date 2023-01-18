<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
include 'f/main.php';
$main = new Main();

$action = false;

if(isset($_GET['action'])){
    $action = $_GET['action'];
}
elseif(isset($_SERVER['argv']) && isset($_SERVER['argv'][1])){
    $action = $_SERVER['argv'][1];
}

switch ($action){
    case 'sendFromESP':
        $main->sendFromESP();
        break;
    case 'getBotUpdate':
        $main->update();
        break;
    case 'checkEspMess':
        $main->checkESP();
        break;
}