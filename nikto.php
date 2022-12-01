<?php
    require "defectDojo.php";

    $dir            = '/home/defectdojo/logs';
    $file           = '/nikto.json';
    $log            = $dir.$file;
    $url            = 'http://site.homelab.local/';
    $productId      = 2;

    $defectDojo     = new DefectDojo();
    $engagementId   = $defectDojo->getActiveEngagement($productId);
    if(!$engagementId){
        $engagementId = $defectDojo->newEngagement($productId, 'Scan mensal', 'teste');
    }

    if(file_exists($log)){
        unlink($log);
    }

    $cmd = "/usr/bin/docker run --rm -v {$dir}:/tmp sullo/nikto -h {$url} -o /tmp{$file}";

    echo "Iniciando scan\n";
    echo `$cmd`;

    if(!file_exists($log)){
        die ('Erro ao gerar '.$file);
    }

    echo "Scan finalizado com sucesso\nEnviando para o DefectDojo\n";

    //Envio do Nikto para o DefectDojo
    $postData = array(
        'service' => 'site', //identificador para comparação de duplicidade
        'engagement' => $engagementId,
        'verified' => 'false',
        'scan_type' => 'Nikto Scan',
        'skip_duplicates' => 'true',
        'close_old_findings' => 'true',
        'file' => new CURLFile($log),
    );

    echo $defectDojo->importScan($postData) ? "Enviado com sucesso\n" : "Falha ao enviar scan\n";
?>
