<?php
    include 'crawler.php';
    function verificarCrawler(){
        $info = file_get_contents("..\Utilities\URLS.txt");

        $info=preg_split('/\s+/',$info);

        for($i=1; $i<count($info); $i=$i+2){
            if($info[$i]=='N'){
                startCrawler($info[$i-1]);
            }
        }
    }

    function sugerirCorrec($query){
        $SolrSug = 'http://localhost:8983/solr/Proyecto_BRIW1/suggest?suggest=true&suggest.build=true&suggest.q=';
        $SolrSug .= urlencode($query);

        $responseArray = json_decode($responseJson, true);

        // Extraer las sugerencias
        $suggestions = $responseArray['suggest']['mySuggester'][$query]['suggestions'];

        // Procesar cada sugerencia para extraer la palabra o frase relevante
        if(!empty($suggestions)){
            foreach ($suggestions as $suggestion) {
                // Dividir la sugerencia en palabras
                $words = explode(' ', $suggestion['term']);

                // Buscar la primera palabra que comience con el término de búsqueda
                foreach ($words as $word) {
                    if (stripos(strip_tags($word), $query) === 0) {
                        echo 'Tal vez buscabas ' . $word . "<br>";
                        break;
                    }
                }
            }
        }else{
            echo 'No hay sugerencias. <br/>';
        }

    }

?>