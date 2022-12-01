<?php

class DefectDojo
{

    private $token;
    private $url;

    public function __construct(){
        $this->token = file_get_contents('tokenDefectDojo.ini');
        $this->url   = 'http://defectdojo.homelab.local:8080';
    }

    public function getActiveEngagement(int $productId): int|false
    {
        $ch = curl_init();
        $header = array();
        $header[] = 'Authorization: Token ' . $this->token;

        curl_setopt($ch, CURLOPT_URL, $this->url . '/api/v2/engagements/?active=true&status=In%20Progress&product=' . $productId);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);

        $return = json_decode($server_output,1);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close ($ch);

        if($httpcode == '200' && $return['count'] == 0){
            return false;
        }elseif($httpcode == '200' && $return['count'] == 1){
            return $return['results'][0]['id'];
        }else{
            echo 'Erro ao buscar engagements do produto ('.$productId. ') httpcode: '.$httpcode."\n";
            var_dump($return);
            exit();
        }
    }

    public function newEngagement(int $productId, string $name, string $description): int|false
    {
        $today      = new DateTime('now');
        $today      = $today->format('Y-m-d');
        $tomorrow   = new DateTime('tomorrow');
        $tomorrow   = $tomorrow->format('Y-m-d');

        $postData = array(
            'name' => $name,
            'description' => $description,
            'product' => $productId,
            'target_start' => $today,
            'target_end' => $tomorrow,
            'status' => 'In Progress',
        );
        
        $ch = curl_init();
        $header = array();
        $header[] = 'content-type: application/json';
        $header[] = 'Authorization: Token ' . $this->token;

        curl_setopt($ch, CURLOPT_URL, $this->url . '/api/v2/engagements/');
        curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        $server_output = curl_exec($ch);

        $return = json_decode($server_output,1);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close ($ch);

        if($httpcode == '201'){
            return $return['id'];
        }else{
            return false;
        }
    }

    public function closeEngagement(int $engagementId): bool
    {
        $ch = curl_init();
        $header = array();
        $header[] = 'Authorization: Token ' . $this->token;

        curl_setopt($ch, CURLOPT_URL, $this->url . '/api/v2/engagements/' . $engagementId . '/close/');
        curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST'); 
        curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close ($ch);

        if($httpcode == '200'){
            return true;
        }else{
            return false;
        }
    }

    public function importScan(array $postData): bool
    {
        $ch = curl_init();
        $header = array();
        $header[] = 'Content-Type:multipart/form-data';
        $header[] = 'Authorization: Token ' . $this->token;

        curl_setopt($ch, CURLOPT_URL, $this->url . '/api/v2/import-scan/');
        curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close ($ch);

        //Em alguns casos onde o anexo é muito grande o DefectDojo pode retornar 504 porém vai processar o arquivo normalmente.
        if($httpcode == '201' || $httpcode == '504'){
            return true;
        }else{
            return false;
        }
    }
}
?>
