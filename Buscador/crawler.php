<?php
require '../vendor/autoload.php';
//$resp = var_dump(class_exists('Symfony\Component\DomCrawler\Crawler'));
//echo $resp;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Link;
use GuzzleHttp\Psr7\Uri;
use Symfony\Component\DomCrawler\UriResolver;

$client = new Client();

$visitedUrls = [];

$keywordsToCategory = [
    'tecnología' => ['tech', 'gadgets', 'software', 'hardware', 'smartphone', 'AI', 'inteligencia artificial', 'robotics', 'robótica', 'computing', 'informática'],
    'política' => ['política', 'gobierno', 'elecciones', 'leyes', 'partidos políticos', 'democracia', 'senado', 'congreso', 'parlamento'],
    'economía' => ['economía', 'mercado', 'finanzas', 'inversiones', 'banco', 'bolsa', 'fiscal', 'comercio', 'negocios', 'emprendedores'],
    'salud' => ['salud', 'medicina', 'doctor', 'hospital', 'clínica', 'tratamiento', 'enfermedad', 'bienestar', 'nutrición', 'ejercicio'],
    'ciencia' => ['ciencia', 'investigación', 'estudio', 'datos', 'descubrimiento', 'espacio', 'física', 'química', 'biología', 'ecología'],
    'deportes' => ['deportes', 'fútbol', 'baloncesto', 'tenis', 'atletismo', 'gimnasia', 'competición', 'equipo', 'entrenamiento'],
    'entretenimiento' => ['entretenimiento', 'cine', 'películas', 'música', 'conciertos', 'famosos', 'televisión', 'series', 'celebridades'],
    'tecnología' => ['tecnología', 'innovación', 'gadgets', 'aplicaciones', 'software', 'hardware', 'internet', 'computación', 'redes sociales'],
    'educación' => ['educación', 'aprendizaje', 'escuela', 'universidad', 'estudiantes', 'profesores', 'cursos', 'academia'],
    'viajes' => ['viajes', 'turismo', 'destinos', 'vacaciones', 'aventura', 'cultura', 'vuelos', 'hoteles'],
    'gastronomía' => ['gastronomía', 'cocina', 'recetas', 'restaurantes', 'comida', 'bebida', 'chef', 'nutrición'],
    'medio ambiente' => ['medio ambiente', 'naturaleza', 'conservación', 'ecología', 'sostenibilidad', 'reciclaje', 'energía renovable'],
 ];

function getLinks($html, $baseUri, &$visitedUrls, $limit = 10) {
    $crawler = new Crawler($html, $baseUri);
    
    $links = $crawler->filter('a')->links();
    $urls = [];
    foreach ($links as $link) {
        $uri = $link->getUri();
        // Ignorar enlaces que no son HTTP/HTTPS o que son enlaces de JavaScript
        if (!preg_match('/^https?:\/\//i', $uri) || preg_match('/javascript:void\(0\);?/i', $uri)) {
            continue;
        }
        // Crear un objeto Uri y eliminar el fragmento
        $uriObject = new Uri($uri);
        $uriWithoutFragment = (string) $uriObject->withFragment('');
        
        // Comprobar si el enlace ya ha sido añadido a la lista de visitados
        if (!isset($visitedUrls[$uriWithoutFragment])) {
            $visitedUrls[$uriWithoutFragment] = true;
            $urls[$uriWithoutFragment] = true;
            if (count($urls) >= $limit) {
                break; // Salir del bucle si se alcanza el límite
            }
        }
    }
    return array_keys($urls); // Devolver solo las claves del array, que son los enlaces únicos
}



// Función para rastrear una URL y devolver el contenido
function fetchUrl($client, $url) {
    try {
        $response = $client->request('GET', $url);
        return (string) $response->getBody();
    } catch (RequestException $e) {
        echo "Error: " . $e->getMessage() . "\n";
        return null;
    }
}

// URL inicial
//$startUrl = 'https://www.muyinteresante.es/';
startCrawler('https://www.bbc.com/mundo');
// Profundidad 1
function startCrawler($startUrl){
    global $client;
    global $visitedUrls;
    global $keywordsToCategory;

    $client = new Client();    

    $html = fetchUrl($client, $startUrl);
    if ($html) {
        $depth1Links = getLinks($html, $startUrl, $visitedUrls, 5);
        foreach ($depth1Links as $linkDepth1) {
            // Profundidad 2
            $depth2Html = fetchUrl($client, $linkDepth1);
            if ($depth2Html) {
                $depth2Links = getLinks($depth2Html, $linkDepth1, $visitedUrls, 15);
                foreach ($depth2Links as $linkDepth2) {
                    echo "Enlace de nivel 2 encontrado: $linkDepth2\n";
                    $detailsHtml = fetchUrl($client, $linkDepth2);
                    if ($detailsHtml) {
                        $details = getPageDetails($detailsHtml, $linkDepth2, $keywordsToCategory);
                        // Solo imprimir si todos los detalles requeridos están presentes.
                        if ($details) {
                            /*echo "Enlace de nivel 2 encontrado: $linkDepth2\n";
                            echo "Título: " . $details['title'] . "\n";
                            echo "Descripción: " . $details['description'] . "\n";
                            echo "Categoría: " . $details['category'] . "\n";
                            echo "Snippet: " . $details['snippet'] . "\n";*/
                            postToSolr($details);
                        } /*else {
                            echo "La página no tiene todos los detalles requeridos y no será impresa.\n";
                        }*/
                    }
                }
            }
        }
    }
}

function getPageDetails($html, $url, $keywordsToCategory) {
    $crawler = new Crawler($html);

    // Intentar obtener cada uno de los detalles de la página.
    $title = getSafeText($crawler, 'title');
    $description = getSafeAttribute($crawler, 'meta[name="description"]', 'content');
    $category = assignCategory($title, $description, $keywordsToCategory);
    $snippet = getSafeText($crawler, 'main p, .content p, article p, div p');

    // Asegurarte de que al menos tengas título y descripción.
    if ($title && $description) {
        return [
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'snippet' => $snippet ?: 'No disponible', // Proporciona un valor predeterminado si no hay snippet
            'url' => $url
        ];
    }

    // Si alguno de los campos está vacío, devuelve null o un arreglo vacío.
    return null;
}

function getSafeText(Crawler $crawler, $selector) {
    $text = '';
    try {
        $text = $crawler->filter($selector)->text();
    } catch (\InvalidArgumentException $e) {
        // Maneja el caso en que el selector no existe, por ejemplo, puedes imprimir un mensaje o simplemente continuar.
        //echo "Selector '{$selector}' no encontrado.";
    }
    return $text;
}

function getSafeAttribute(Crawler $crawler, $selector, $attribute) {
    $value = '';
    // Intentar con el caso exacto primero
    try {
        $value = $crawler->filter($selector)->attr($attribute);
    } catch (\InvalidArgumentException $e) {
        // Ignorar el error y probar con el siguiente selector
    }

    // Si el valor está vacío, probar con un selector XPath insensible a mayúsculas/minúsculas
    if (empty($value)) {
        try {
            // Construye un selector XPath que sea insensible a mayúsculas/minúsculas para el nombre del atributo
            $lowerAttribute = strtolower($attribute);
            $expression = "translate(@name, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz') = '$lowerAttribute'";
            $value = $crawler->filterXPath("//meta[$expression]")->attr('content');
        } catch (\InvalidArgumentException $e) {
            // Manejar el caso en que el selector no existe
            //echo "Atributo '{$attribute}' no encontrado para el selector '{$selector}'.";
        }
    }

    return $value;
}


function assignCategory($title, $description, $keywordsToCategory) {
    $textToAnalyze = strtolower($title . ' ' . $description);
    $categoryScores = [];

    foreach ($keywordsToCategory as $category => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($textToAnalyze, strtolower($keyword)) !== false) {
                if (!isset($categoryScores[$category])) {
                    $categoryScores[$category] = 0;
                }
                $categoryScores[$category]++;
            }
        }
    }

    arsort($categoryScores); // Ordena por puntuación de forma descendente
    $topCategory = key($categoryScores); // Obtiene la categoría con la puntuación más alta

    return $topCategory ?: 'general'; // 'general' como categoría por defecto
}

function postToSolr($data) {
    $client = new GuzzleHttp\Client();
    $solrUrl = 'http://localhost:8983/solr/CoreBRIW/update?commit=true'; // Asegúrate de reemplazar con tu URL de Solr y nombre de core

    // Asegúrate de que los datos se envíen como un documento JSON y no como una cadena JSON serializada.
    $jsonDocument = [
        'id' => $data['id'] ?? uniqid(), // Genera un ID único si no se proporciona uno
        'title' => $data['title'],
        'description' => $data['description'],
        'category' => $data['category'],
        'snippet' => $data['snippet'],
        'url' => $data['url']
        // No es necesario enviar "_version_", Solr lo manejará automáticamente
    ];

    try {
        $response = $client->post($solrUrl, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([$jsonDocument]) // Envía el documento como parte de un arreglo
        ]);

        echo "Datos indexados en Solr. Respuesta: " . $response->getBody() . "\n";
    } catch (RequestException $e) {
        echo "Error al indexar en Solr: " . $e->getMessage() . "\n";
    }
}

?>

