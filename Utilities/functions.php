<?php
    function verificarCrawler(){
        $info = file_get_contents("..\Utilities\URLS.txt");
        var_dump($info);

        $info=preg_split('/\s+/',$info);

        var_dump($info);

        for($i=1; $i<count($info); $i=$i+2){
            if($info[$i]=='N'){
                print "Enviar al crawler ".$info[$i-1];
            }
        }
    }
?>