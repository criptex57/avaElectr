<?php
include 'db.php';
include 'requests.php';
include '../jpgraph-4.4.1/src/jpgraph.php';
include '../jpgraph-4.4.1/src/jpgraph_line.php';

class Main {
    public $db;
    public $requests;
    private static $hash = 'yAuZvdf7J6iZ5Y95h95vG3eV8G924Sz4';
    private static $requestInterval = 120;

    public function __construct(){
        $this->db = new db();
        $this->requests = new requests();
    }

    public function update(){
        $updateId = $this->db->getLastUpdate();
        $response = $this->requests->getUpdates($updateId);

        if($response){
            $response = json_decode($response, true);
            if(isset($response['result'])){
                $count = 0;

                foreach ($response['result'] as $message) {
                    if(isset($message['update_id'])){
                        if($message['update_id'] == $updateId){
                            continue;
                        }

                        $this->db->setTgUpdate($message['update_id']);
                    }

                    if(isset($message['message'])){
                        $subscriber = $this->db->getSubscriberByTgId((int)$message['message']['from']['id']);

                        if(!$subscriber){
                            $count++;
                            $this->db->createSubscribers($message['message']['from']['first_name'], $message['message']['from']['last_name'], $message['message']['from']['id']);
                            $this->requests->sendMessage('Оновлення включено', $message['message']['from']['id']);
                        }
                        elseif(isset($subscriber['status']) && $subscriber['status'] == db::$subscribersStatus['disable']) {
                            $this->db->enableSubscribers($message['message']['from']['id']);
                            $this->requests->sendMessage('Оновлення включено', $message['message']['from']['id']);
                        }

                        if(isset($message['message']['text'])){
                            switch ($message['message']['text']) {
                                case '/getbaro':
                                    $this->requests->sendGraph($message['message']['from']['id']);
                                    break;
                                case '/gettemp':
                                    $this->requests->sendGraph($message['message']['from']['id'], true);
                                    break;
                                case '/getbarorange':
                                    $this->requests->sendGraph($message['message']['from']['id'], true, ['lasthour' => true]);
                                    break;
                            }
                        }
                    }
                }

                echo "Create $count subscribers";
            }
        }
    }

    public function checkESP(){
        if($this->db->isOldEspReq()){
            $allSubscribers = $this->db->getSubscribers();

            if($allSubscribers){
                foreach ($allSubscribers as $subscriber){
                    if(isset($subscriber['status']) && $subscriber['status'] == db::$subscribersStatus['enable']){
                        $subs = $this->db->getSubscriberByTgId($subscriber['telegramId']);

                        if($subs && isset($subs['id'])){
                            $lmebs = $this->db->getLastMessageEventBySubscribers($subs['id']);
                            $lastEspRequest = $this->db->getLastEspRequest();

                            if($lmebs['lastRequestsEspId'] != $lastEspRequest['id']){
                                $response = $this->requests->sendMessage('Світло вимкнено у '.date('H:i', (time()-120)), $subscriber['telegramId']);
                                echo $response;
                            }
                        }
                    }
                }
            }
        }
    }

    public function sendFromESP(){
        if($_GET && isset($_GET['hash']) && $_GET['hash'] == self::$hash){
            if($this->db->isOldEspReq()){
                $allSubscribers = $this->db->getSubscribers();

                if($allSubscribers){
                    foreach ($allSubscribers as $subscriber){
                        if(isset($subscriber['status']) && $subscriber['status'] == db::$subscribersStatus['enable']){
                            $response = $this->requests->sendMessage('Світло увімкнено у '.date('H:i', (time()-120)), $subscriber['telegramId']);
                            echo $response;
                        }
                    }
                }
            }

            $this->db->createEspRequest(
                isset($_GET['temp'])?$_GET['temp']:null,
                isset($_GET['pressure'])?$_GET['pressure']:null
            );
        }
    }

    private function getGraphArr(){
        $result = [];
        $prevCreated = false;
        $prevValue = false;

        $db = new db();
        $espRequests = $db->getEspRequests();

        foreach ($espRequests as $espRequest) {
            $currentCreated = strtotime($espRequest['created']);

            if(isset($_GET['temp']) && $_GET['temp']){
                $currentValue = $espRequest['temp'];
            }
            else {
                $currentValue = $espRequest['baro'];
            }

            if(!$prevValue){
                $prevValue = $currentValue;
            }

            if(!$prevCreated){
                $prevCreated = $currentCreated;
            }

            if($prevCreated){
                $difference = $currentCreated - $prevCreated;

                if($difference > (self::$requestInterval*2)){
                    $missedRequests = round($difference / self::$requestInterval);
                    $diffValue = $currentValue - $prevValue;
                    $sum = $diffValue / $missedRequests;

                    if($missedRequests){
                        for($i = 0; $i <= $missedRequests; $i++){
                            $prevValue += $sum;
                            $result[] = round($prevValue, 2);
                        }
                    }
                }
            }

            $result[] = $currentValue;
            $prevValue = $currentValue;
            $prevCreated = $currentCreated;
        }

        return $result;
    }

    public function getGraph(){
        $ydata =  $this->getGraphArr();
        $xdata = false;

        $graph = new Graph(1000, 500, 'auto', 10, true);
        $graph->SetScale('textlin'); // Указываем, какие оси использовать:
        $lineplot = new LinePlot($ydata, $xdata); // Создаем линейный график, передадим ему значения
        $lineplot->SetColor('forestgreen'); // цвет кривой
        $graph->Add($lineplot); // Присоединяем кривую к графику

        // Даем графику имя:
        $title = isset($_GET['temp'])&&$_GET['temp']?'Температура у кімнаті за останню добу':'Атм. тиск за останню добу';
        $graph->title->Set($title);

        //TTF-шрифты, которые поддерживают кириллицу
        $graph->title->SetFont(FF_ARIAL, FS_NORMAL);
        $graph->xaxis->title->SetFont(FF_VERDANA, FS_ITALIC);
        $graph->yaxis->title->SetFont(FF_TIMES, FS_BOLD);

        //Название осей
        $graph->xaxis->title->Set('Час');
        $graph->yaxis->title->Set('');

        //Выделим оси цветом:
        $graph->xaxis->SetColor('#FFFFFF');
        $graph->yaxis->SetColor('#000000');

        $lineplot->SetWeight(3); // Зададим толщину кривой:

        // Обозначим точки звездочками, задав тип маркера:
        //$lineplot->mark->SetType(MARK_FILLEDCIRCLE);
        // Выведем значения над каждой из точек:
        //$lineplot->value->Show();
        // Фон графика зальем градиентом:
        //$graph->SetBackgroundGradient('ivory', 'black');

        $graph->SetShadow(4); // Придадим графику тень:
        $graph->Stroke(); //Выведем получившееся изображение в браузер
    }
}