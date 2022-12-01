<?php
    require "defectDojo.php";

    $dir            = '/home/defectdojo/logs';
    $file           = '/trivy.json';
    $log            = $dir.$file;
    $url            = 'http://site.homelab.local/';
    $productId      = 2;

    $imagens = array(
        'base' => 'sasanlabs/owasp-vulnerableapp:unreleased',
        'jsp'  => 'sasanlabs/owasp-vulnerableapp-jsp:latest',
        'php'  => 'sasanlabs/owasp-vulnerableapp-php:latest',
    );

    $defectDojo     = new DefectDojo();
    $engagementId   = $defectDojo->getActiveEngagement($productId);
    if(!$engagementId){
        $engagementId = $defectDojo->newEngagement($productId, 'Scan mensal', 'teste');
    }


    //Trivy
    foreach($imagens as $identificador => $imagem){
        if(file_exists($log)){
            unlink($log);
        }
        $cmd = "/usr/bin/docker run -i --rm -e TRIVY_TIMEOUT_SEC='500s' --mount type=bind,source={$dir},target=/output aquasec/trivy image {$imagem} -f json --output /output/{$file}";

        echo `$cmd`;
        if(!file_exists($log)){
            die ('erro ao gerar '.$file.' na imagem: '.$imagem);
        }

        //Envio do Trivy para o DefectDojo
        $postData = array(
            'service' => $identificador, //identificador para comparação de duplicidade
            'engagement' => $engagementId,
            'scan_type' => 'Trivy Scan',
            'skip_duplicates' => 'true',
            'close_old_findings' => 'true',
            'file' => new CURLFile($log),
        );

        echo $defectDojo->importScan($postData) ? "Enviado com sucesso\n" : "Falha ao enviar scan\n";
    }
?>