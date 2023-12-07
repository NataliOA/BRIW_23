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
    
    function removeStopWords($array){
    $stopWords = ["el", "la", "de", "en", "y", "a", "lo", "los", "mi", "quedó", "queremos","es", "era","solo", "esta", "quien", "quienes", "quiere", "quiza", "quizas", "quizá", "quizás", "quién", "quiénes", "qué", "r", "raras", "realizado", "realizar", "realizó", "repente", "respecto", "s", "sabe", "las", "sabemos", "saben", "saber", "sabes", "sal", "salvo", "se", "sea", "seamos", "sean", "seas", "según", "segun", "segunda", "segundo", "seis", "ser", "sera", "seremos", "será", "serán", "serás", "seré", "serán", "sería", "seríais", "seríamos", "serían", "serías", "seáis", "señaló", "si", "sido", "siempre", "siendo", "siete", "sigue", "siguiente", "sin", "sino", "sobre", "sois", "sola", "solamente", "solas", "solo", "solos", "somos", "son", "soy", "soyos", "su", "supuesto", "sus", "suya", "suyas", "suyo", "suyos", "sé", "sí", "sólo", "t", "tal", "tambien", "también", "tampoco", "tan", "tanto", "tarde", "te", "temprano", "tendremos", "tendrá", "tendrán", "tendrás", "tendré", "tendréis", "tendría", "tendríais", "tendríamos", "tendrían", "tendrías", "tened", "teneis", "tenemos", "tener", "tenga", "tengamos", "tengan", "tengas", "tengo", "tengáis", "tenida", "tenidas", "tenido", "tenidos", "teniendo", "tenéis", "tenía", "teníais", "teníamos", "tenían", "tenías", "tercera", "ti", "tiempo", "tiene", "tienen", "tienes", "toda", "todas", "todavía", "todo", "todos", "total", "trabaja", "trabajais", "trabajamos", "trabajan", "trabajar", "trabajas", "trabajo", "tras", "trata", "través", "tres", "tu", "tus", "tuve", "tuviera", "tuvierais", "tuvieran", "tuvieras", "tuvieron", "tuviese", "tuvieseis", "tuviesen", "tuvieses", "tuvimos", "tuviste", "tuvisteis", "tuviéramos", "tuviésemos", "tuvo", "tuya", "tuyas", "tuyo", "tuyos", "tú", "u", "ultimo", "un", "una", "unas", "uno", "unos", "usa", "usais", "usamos", "usan", "usar", "usas", "uso", "usted", "ustedes", "v", "va", "vais", "valor", "vamos", "van", "varias", "varios", "vaya", "veces", "ver", "verdad", "verdadera", "verdadero", "vez", "vosotras", "vosotros", "voy", "vuestra", "vuestras", "vuestro", "vuestros", "w", "x", "y", "ya", "yo", "z", "él", "éramos", "ésa", "ésas", "ése", "ésos", "ésta", "éstas", "éste", "éstos", "última", "últimas", "último", "últimos", "ahí", "ahora", "al", "algo", "algunas", "algunos", "alli", "allí", "amigo", "amigos", "ante", "antes", "apenas", "aquí", "así", "aunque", "ayer", "bajo", "bastante", "bien", "cabe", "cada", "casi", "cierto", "ciertos", "cinco", "como", "con", "conmigo", "conseguimos", "conseguir", "consigo", "consigue", "consiguen", "consigues", "contigo", "contra", "cual", "cuales", "cuando", "cuanto", "cuatro", "cuenta", "da", "dado", "dan", "dar", "de", "del", "demás", "dentro", "desde", "donde", "dos", "durante", "él", "ella", "ellas"];
      
        return array_filter($array, function($word) use ($stopWords) {
            return !in_array(strtolower($word), $stopWords);
        });
    }
?>