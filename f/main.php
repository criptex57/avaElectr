<?php
include 'db.php';
include 'requests.php';

class Main {
    public $db;
    public $requests;
    private static $hash = 'yAuZvdf7J6iZ5Y95h95vG3eV8G924Sz4';

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
                            $this->requests->sendMessage('Обновления включены', $message['message']['from']['id']);
                        }
                        elseif(isset($subscriber['status']) && $subscriber['status'] == db::$subscribersStatus['disable']) {
                            $this->db->enableSubscribers($message['message']['from']['id']);
                            $this->requests->sendMessage('Обновления включены', $message['message']['from']['id']);
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
                                $response = $this->requests->sendMessage('Свет отключен в '.date('H:i'), $subscriber['telegramId']);
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
                            $response = $this->requests->sendMessage('Свет включен в '.date('H:i'), $subscriber['telegramId']);
                            echo $response;
                        }
                    }
                }
            }

            $this->db->createEspRequest('10');
        }
    }
}