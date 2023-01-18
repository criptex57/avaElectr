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
        $main->createEspRequest(); //Запрос с ESP
        break;
    case 'checkEspMess':
        $main->checkLastEspRequest(); //Проверить как давно были запросы с ESP
        break;
    case 'getBotUpdate':
        $main->getMessageFromBot(); //Получить новые сообщения от бота
        break;
    case 'getGraph':
        $main->getGraph(); //Получить картинку с графиком
        break;
}