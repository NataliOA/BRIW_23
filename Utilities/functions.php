<?php
    include '..\Buscador\crawler.php';
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
        $SolrSug = 'http://localhost:8983/solr/Proyecto_BRIW1/suggest?suggest=true&suggest.q=';
        $SolrSug .= urlencode($query);
	
	$ch = curl_init($SolrSug);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, false);
	$responseJSON = curl_exec($ch);
	curl_close($ch);

        // Procesar cada sugerencia para extraer la palabra o frase relevante
        if(!empty($responseJSON)){
	    $responseArray = json_decode($responseJSON, true);

            // Extraer las sugerencias
            $suggestions = $responseArray['suggest']['mySuggester'][$query]['suggestions'];
	    $sugerir = 'Tal vez buscabas ';
	    $palabrasSug = [];	    

            foreach ($suggestions as $suggestion) {
                // Dividir la sugerencia en palabras
                $words = explode(' ', $suggestion['term']);

                foreach ($words as $word) {
                    if (stripos(strip_tags($word), $query) === 0) {
			if (!in_array($word, $palabrasSug)) {
            		$palabrasSug[] = $word;
        		}
                        break;
                    }
                }
		
            }
	    foreach($palabrasSug as $s){
			$sugerir.=$s . ' ';
	    }
	    echo($sugerir);
        }else{
            echo 'No hay sugerencias. <br/>';
        }

    }

?>