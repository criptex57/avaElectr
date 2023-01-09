<?php
class requests {
    private $host = 'https://api.telegram.org/bot5651587480:AAHen7XxpHym_kCEnSivaglOcc3NTPGwaFA/';
    private $db;

    public function __construct(){
        $this->db = new db;
    }

    public function sendMessage($text, $userId, $type = 'message'){
        $addr = $this->host."sendMessage";

        $response = $this->sendRequest($addr, [
            'chat_id' => $userId,
            'text' => $text
        ]);
        $responseArr = json_decode($response, true);

        if($responseArr && isset($responseArr['error_code']) && $responseArr['error_code']){
            switch ($responseArr['error_code']) {
                case 403:
                    $this->db->disableSubscribers($userId);
                    break;
            }
        }
        else {
            $subscriber = $this->db->getSubscriberByTgId($userId);
            $lastEspRequest = $this->db->getLastEspRequest();

            if($subscriber){
                $this->db->setMessageEvent(
                    $text,
                    $type,
                    isset($lastEspRequest['id'])?$lastEspRequest['id']:null,
                    $subscriber['id']
                );
            }
        }

        return $response;
    }

    public function getUpdates($updateId){
        $addr = $this->host."getUpdates";

        if($updateId){
            $addr .= '?offset='.$updateId;
        }

        return $this->sendRequest($addr);
    }

    private function sendRequest($addr, $param = []){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $addr,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Content-type: application/json"],
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_POSTFIELDS => json_encode($param),
            CURLOPT_POST => true,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT => 100
        ));

        return curl_exec($curl);
    }
}