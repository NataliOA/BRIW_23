<?php
    include 'crawler.php';
    function verificarCrawler(){
        $info = file_get_contents("..\Utilities\URLS.txt");

        $info=preg_split('/\s+/',$info);

        for($i=1; $i<count($info); $i=$i+2){
            if($info[$i]=='N'){
                fetchUrl($info[$i+1]);
            }
        }
    }
?>