<?php
class db {
    static $host = '127.0.0.1';
    static $port = '3306';
    static $db = 'avaElectr';
    static $user = 'root';
    static $password = 'u8y4C53yJc4gFz';
    static $subscribersStatus = [
        'disable' => 0,
        'enable' => 1
    ];

    private $dbConnection;

    public function __construct(){
        $this->dbConnection = new mysqli(
            self::$host,
            self::$user,
            self::$password,
            self::$db,
            self::$port
        );

        if ($this->dbConnection->connect_error) {
            die("Connection failed: " . $this->dbConnection->connect_error);
        }
    }

    public function getLastMessageEventBySubscribers($subscribersId){
        $query = "SELECT * FROM messages WHERE `subscriberId` = ".$subscribersId." ORDER BY created DESC  LIMIT 1";
        $response = $this->dbConnection->query($query);
        $result = false;

        if($response->num_rows > 0){
            foreach ($response as $item){
                $result = $item;
            }
        }

        return $result;
    }

    public function setMessageEvent($text, $type, $lastRequestsEspId, $subscriberId){
        $query = "INSERT INTO messages (`text`, `type`, `lastRequestsEspId`, `subscriberId`) VALUES ('".$text."', '".$type."', '".$lastRequestsEspId."', '".$subscriberId."')";
        $this->dbConnection->query($query);

        return $this->dbConnection->insert_id;
    }

    public function disableSubscribers($tgId){
        $query = "UPDATE subscribers SET status = ".self::$subscribersStatus['disable']." WHERE telegramId = ".$tgId;
        return $this->dbConnection->query($query);
    }

    public function enableSubscribers($tgId){
        $query = "UPDATE subscribers SET status = ".self::$subscribersStatus['enable']." WHERE telegramId = ".$tgId;
        return $this->dbConnection->query($query);
    }

    public function createSubscribers($firstName, $lastName, $telegramId){
        $query = "INSERT INTO subscribers (`first_name`, `last_name`, `telegramId`, `status`) VALUES ('".$firstName."', '".$lastName."', '".$telegramId."', '".self::$subscribersStatus['enable']."')";
        $this->dbConnection->query($query);

        return $this->dbConnection->insert_id;
    }

    public function getSubscriberByTgId($tgId){
        $query = "SELECT * FROM subscribers WHERE `telegramId` = ".$tgId." LIMIT 1";
        $response = $this->dbConnection->query($query);
        $result = false;

        if($response->num_rows > 0){
            foreach ($response as $item){
                $result = $item;
            }
        }

        return $result;
    }

    public function setTgUpdate($updateId){
        $query = "INSERT INTO updates (`update_id`) VALUES ('".$updateId."')";
        $this->dbConnection->query($query);

        return $this->dbConnection->insert_id;
    }

    public function getLastUpdate(){
        $query = "SELECT * FROM updates ORDER BY created DESC LIMIT 1";
        $data = $this->dbConnection->query($query);
        $response = false;

        if($data->num_rows > 0){
            foreach ($data as $datum) {
                if(isset($datum['update_id'])){
                    $response = $datum['update_id'];
                }
                break;
            }
        }

        return $response;
    }

    public function getSubscribers(){
        $query = "SELECT * FROM subscribers";
        $response = $this->dbConnection->query($query);
        $result = [];

        if($response->num_rows > 0){
            foreach ($response as $item){
                $result[] = $item;
            }
        }

        return $result;
    }

    public function createEspRequest($temp = null, $pressure = null){
        print_r([$temp, $pressure]);
        $query = "INSERT INTO requestsFromEsp (`baro`, `temp`) VALUES ('".(string)$pressure."', '".(string)$temp."')";
        $this->dbConnection->query($query);
        return $this->dbConnection->insert_id;
    }

    public function getEspRequests($from = false, $to = false){
        if(!$from){
            $from = date('Y-m-d H:i:s', strtotime('-1 day'));
        }

        if(!$to){
            $to = date('Y-m-d H:i:s', time()) ;
        }

        $query = "SELECT * FROM requestsFromEsp WHERE created > '$from' AND created < '$to'";


        $data = $this->dbConnection->query($query);
        $response = [];

        if($data->num_rows > 0){
            foreach ($data as $datum) {
                $response[] = $datum;
            }
        }

        return $response;
    }

    public function getLastEspRequest(){
        $query = "SELECT * FROM requestsFromEsp ORDER BY created DESC LIMIT 1";
        $data = $this->dbConnection->query($query);
        $response = false;

        if($data->num_rows > 0){
            foreach ($data as $datum) {
                $response = $datum;
                break;
            }
        }

        return $response;
    }

    public function isOldEspReq(){
        $response = $this->getLastEspRequest();
        return strtotime('-5 minutes') > strtotime(isset($response['created'])?$response['created']:0);
    }
}