<?php
include 'db.php';
include 'requests.php';
include 'average.php';
include '../jpgraph-4.4.1/src/jpgraph.php';
include '../jpgraph-4.4.1/src/jpgraph_line.php';

class Main {
    public $db;
    public $requests;
    private static $hash = 'yAuZvdf7J6iZ5Y95h95vG3eV8G924Sz4';
    private static $requestInterval = 120;
    public static $cardinalDirections = [
        'N' => 'Північний',
        'NE' => 'Північно-східний',
        'E' => 'Східний',
        'SE' => 'Південно-східний',
        'S' => 'Південний',
        'SW' => 'Південно-західний',
        'W' => 'Західний',
        'NW' => 'Північно-західний',
    ];

    public function __construct(){
        $this->db = new db();
        $this->requests = new requests();
    }

    public function getMessageFromBot(){
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

                    $userTgId = $message['message']['from']['id'];

                    if(isset($message['message'])){
                        $subscriber = $this->db->getSubscriberByTgId((int)$message['message']['from']['id']);

                        if(!$subscriber){
                            $count++;
                            $this->db->createSubscribers($message['message']['from']['first_name'], $message['message']['from']['last_name'], $userTgId);
                            $this->requests->sendMessage('Оновлення увімкнено', $userTgId);
                        }
                        elseif(isset($subscriber['status']) && $subscriber['status'] == db::$subscribersStatus['disable']) {
                            $this->db->enableSubscribers($message['message']['from']['id']);
                            $this->requests->sendMessage('Оновлення увімкнено', $userTgId);
                        }

                        if(isset($message['message']['text'])){
                            $this->runBotEvent($message['message']['text'], $message['message']['from']['id']);
                        }
                    }
                }

                echo "Create $count subscribers";
            }
        }
    }

    private function runBotEvent($text, $userTgId){
        switch ($text) {
            case '/getweather':
                $this->requests->sendMessage($this->getWeatherMessage(), $userTgId);
                break;
            case '/getcurrentweather':
                $this->requests->sendMessage($this->getWeatherMessage(time()), $userTgId);
                break;
            case '/getbaro':
                $this->requests->sendGraph($userTgId);
                break;
            case '/gettemp':
                $this->requests->sendGraph($userTgId, true);
                break;
            case '/getbarorange':
                $this->requests->sendMessage("Вкажіть період, за який хочете отримати графік у форматі: \n 08.01.2023-16.01.2023 або \n 08-16 або \n 08.01.2023 або \n просто число 08", $userTgId);
                break;
            case '/gettemprange':
                $this->requests->sendMessage("Вкажіть період, за який хочете отримати графік у форматі: \n t08.01.2023-16.01.2023 або \n t08-16 або \n t08.01.2023 або \n просто число t08", $userTgId);
                break;
            default:
                if(preg_match('#^(t)?(\d{2})\.(\d{2})\.(\d{4})\-(\d{2})\.(\d{2})\.(\d{4})$#', $text, $match)){
                    $this->getRange(ltrim($text, 't'), $userTgId, isset($match[1])&&$match[1]=='t');
                }
                elseif(preg_match('#^(t)?(\d{2})\-(\d{2})$#', $text, $match)){
                    $this->getRange(ltrim($text, 't'), $userTgId, isset($match[1])&&$match[1]=='t');
                }
                elseif(preg_match('#^(t)?(\d{2})\.(\d{2})\.(\d{4})$#', $text, $match)){
                    $this->getDay(
                        ltrim($text, 't'),
                        $userTgId,
                        isset($match[1])&&$match[1]=='t'
                    );
                }
                elseif(preg_match('#^(t)?(\d{2})$#', $text, $match) && $text <= 31){
                    $this->getDay(
                        $match[2].date('-m-Y'),
                        $userTgId,
                        isset($match[1])&&$match[1]=='t'
                    );
                }
                else {
                    $this->requests->sendMessage('Я вас не понимаю', $userTgId);
                }
                break;
        }
    }

    private function getDay($text, $userTgId, $temp = false){
        $timeFrom = strtotime(trim($text));
        $timeTo = strtotime($text)+(60*60*24);
        $this->requests->sendGraph($userTgId,$temp, ['from' => date('Y-m-d' , $timeFrom), 'to' =>  date('Y-m-d' , $timeTo)]);
    }

    private function getRange($text, $userTgId, $temp = false){
        $date = explode('-', trim($text));

        if(isset($date[0]) && isset($date[1])){
            $date[0] = strlen($date[0]) == 2?$date[0].date('.m.Y'):$date[0];
            $date[1] = strlen($date[1]) == 2?$date[1].date('.m.Y'):$date[1];
            $timeFrom = strtotime($date[0]);
            $timeTo = strtotime($date[1]);

            if($timeFrom <= $timeTo){
                $this->requests->sendGraph($userTgId,$temp, ['from' => date('Y-m-d' , $timeFrom), 'to' =>  date('Y-m-d' , $timeTo)]);
            }
            else {
                $this->requests->sendMessage('Перша дата не може бути більшою за другу', $userTgId);
            }
        }
    }

    public function checkLastEspRequest(){
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

    public function createEspRequest(){
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

    private function getGraphArr($from, $to){
        $result = [];
        $prevCreated = false;
        $prevValue = false;

        $db = new db();
        $valueName = isset($_GET['temp']) && $_GET['temp']?'temp':'baro';
        $espRequests = $db->getEspRequests($from, $to);

        foreach ($espRequests as $key => $espRequest) {
            $currentCreated = strtotime($espRequest['created']);

            $currentValue = $espRequest[$valueName];

            if(!$prevValue){
                $prevValue = $currentValue;
            }

            if(!$prevCreated){
                $prevCreated = $currentCreated;
            }

            if($prevCreated){
                $difference = $currentCreated - $prevCreated;

                if($difference > (self::$requestInterval*2)){
                    if(isset($espRequests[$key + 5])){
                        $currentValue = $espRequests[$key + 5][$valueName];
                    }

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

        $movingAverage = new \Dcvn\Math\Statistics\MovingAverage();
        $movingAverage->setPeriod(70);

        if(empty($result)){
            $result = [0,0];
        }

        $result = $movingAverage->getCalculatedFromArray($result);
        if($valueName == 'baro'){
            $result = $this->fromPaToMm($result);
        }

        return $result;
    }

    private function fromPaToMm($arr){
        $result = [];
        $amendment = 7;

        foreach ($arr as $item){
            $result[] = round((($item+$amendment)/10)*(7.501), 2);
        }

        return $result;
    }

    public function getGraph(){
        $from = isset($_GET['from'])?date("Y-m-d H:i:s", strtotime($_GET['from'])):false;
        $to = isset($_GET['to'])?date("Y-m-d H:i:s", strtotime($_GET['to'])):false;
        $ydata =  $this->getGraphArr($from, $to);
        $xdata = false;

        $graph = new Graph(1200, 500, 'auto', 10, true);
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
        $graph->xaxis->title->Set('');
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

    public function sendWeatherToAllSubs($time){
        $this->requests->sendMessageToAll($this->getWeatherMessage($time));
    }

    public function getWeatherMessage($time = false){
        if(!$time){
            $time = strtotime('+1 day');
        }

        $title = 'Прогноз погоди на '.date('d.m.Y', $time)."\nм. Первомайськ\n\n";
        $text = "";
        $weather = $this->getWeather(date('Y-m-d', $time));
        $temp = "";

        if($weather){
            foreach ($weather as $w){
                $temp .= "{$w['temp']} ";
                $text .= "Час: {$w["time"]}\n";
                $text .= "Температура: {$w['temp']} {$w['cloud']}\n";
                $text .= "Напрямок вітру: {$w["wind"][0]}\n";
                $text .= "Швидкість вітру: {$w['wind'][1]}м/с\n";
                $text .= "Ймовірність опадів (%): {$w['rain']}\n\n";
            }

            $text = $title.$temp."\n\n".$text;
        }

        return $text;
    }

    public function getWeather($date = false){
        $weatherResult = [
            ['time' => '2:00'],
            ['time' => '5:00'],
            ['time' => '8:00'],
            ['time' => '11:00'],
            ['time' => '14:00'],
            ['time' => '17:00'],
            ['time' => '20:00'],
            ['time' => '23:00']
        ];

        if(!$date){
            $date = date('Y-m-d', strtotime('+1 day'));
        }

        $weather = $this->requests->getWeather($date);
        $weather = str_replace("\n", "", $weather);
        $weather = str_replace("\r", "", $weather);
        $weather =  preg_replace("/\s{2,}/",' ',$weather);

        preg_match_all('#<tr class=\"temperature\">(.*)<\/tr>#Ui', $weather, $matches);
        preg_match_all('#Tooltip wind wind-(.*)<\/div>#Ui', $weather, $matchesWind);
        preg_match_all('#weatherIco(.*)title=\"(.*)\"#Ui', $weather, $matchesIco);
        preg_match_all('#<\/tr> <tr> <td class=\"p1 \" >(.*)<\/td> <\/tr> <\/tbody>#Ui', $weather, $matchesRain);

        $rainData = explode('    ', strip_tags($matchesRain[1][0]));
        $rainData = explode(' ', trim($rainData[count($rainData)-1]));
        $tempData = explode(' ', trim(strip_tags($matches[0][0])));

        foreach ($weatherResult as $key => $w) {
            $wind = explode('">', $matchesWind[1][$key]);
            $wind[0] = self::$cardinalDirections[$wind[0]];
            $weatherResult[$key]['wind'] = $wind;
            $weatherResult[$key]['temp'] = str_replace('&deg;', '', $tempData[$key]);
            $weatherResult[$key]['cloud'] = $matchesIco[2][$key];
            $weatherResult[$key]['rain'] = $rainData[$key];
        }

        return $weatherResult;
    }
}