<?php
    require "defectDojo.php";

    $dir            = '/home/defectdojo/logs';
    $file           = '/wpscan.json';
    $log            = $dir.$file;
    $url            = 'http://blog.homelab.local/';
    $productId      = 4;
    $tokenWpScan    = file_get_contents('tokenWpScan.ini');

    $defectDojo     = new DefectDojo();
    $engagementId   = $defectDojo->getActiveEngagement($productId);
    if(!$engagementId){
        $engagementId = $defectDojo->newEngagement($productId, 'Scan mensal', 'teste');
    }

    if(file_exists($log)){
        unlink($log);
    }

    //Scan completo https://github.com/wpscanteam/wpscan/wiki/WPScan-User-Documentation
    //$cmd = "/usr/bin/docker run -i --rm --mount type=bind,source=$dir,target=/output wpscanteam/wpscan:latest --update --url $url --api-token $tokenWpScan --random-user-agent --force --enumerate vp,vt,tt,cb,dbe --plugins-version-detection aggressive --format json --output /output/{$file} 2>&1";

    //Scan rápido apenas para exemplo
    $cmd = "/usr/bin/docker run -i --rm --mount type=bind,source={$dir},target=/output wpscanteam/wpscan:latest --url {$url} --api-token $tokenWpScan --format json --output /output{$file}";

    echo "Iniciando scan\n";
    echo `$cmd`;

    if(!file_exists($log)){
        die ('Erro ao gerar '.$file);
    }

    echo "Scan finalizado com sucesso\nEnviando para o DefectDojo\n";

    //Envio do WpScan para o DefectDojo
    $postData = array(
        'service' => 'wordpress', //identificador para comparação de duplicidade
        'engagement' => $engagementId,
        'verified' => 'false',
        'scan_type' => 'Wpscan',
        'skip_duplicates' => 'true',
        'close_old_findings' => 'true',
        'file' => new CURLFile($log),
    );

    echo $defectDojo->importScan($postData) ? "Enviado com sucesso\n" : "Falha ao enviar scan\n";
?>