<?php

$query = $_GET['query'] ?? ''; // Asegúrate de que el parámetro 'query' está presente
$categoria = $_GET['categoria_busqueda'] ?? ''; // Asegúrate de que el parámetro 'categoria' está presente

include '../Utilities/functions.php';
verificarCrawler();

$palabras = explode(" ", $query);
$sinonimos = [];
$busqueda = [];
$querySin = '';

foreach ($palabras as $p) {
    $querySin .= $p.'+';
}

$count=0;
$expansion = file_get_contents("https://api.datamuse.com/words?v=es&ml=$querySin&max=3");
$sinonimos = json_decode($expansion, true);
foreach($sinonimos as $ar){
    $busqueda[$count]=$ar['word'];
    $count++;
}

// URL del servidor Solr
$solrServerUrl = 'http://localhost:8983/solr/Proyecto_BRIW1/select';

$sins = '';
if(!empty($busqueda)){
    foreach ($busqueda as $sinonimo) {
        $sins .= ' OR ' . $sinonimo;
    }
}

$q =$query.$sins;
// Construye la consulta de búsqueda
$solrQuery = $solrServerUrl.'?q=' . urlencode($q) . '&fl=*,score';
if (!empty($categoria)) {
    $solrQuery .= '&fq=category:"' . urlencode($categoria) . '"';
}

// Realiza la solicitud a Solr
$ch = curl_init($solrQuery);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
$response = curl_exec($ch);
curl_close($ch);

$solrQuery = $solrServerUrl.'?facet.field=category&facet=true&q=' . urlencode($q) . '&fl=*,score';

// Realiza la solicitud a Solr
$ch = curl_init($solrQuery);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
$facet = curl_exec($ch);
curl_close($ch);

// Procesa la respuesta
if ($response) {
    $data = json_decode($response, true);
    $resultados = $data['response']['docs'];
    $facets = json_decode($facet,true);
    $facetados = $facets['facet_counts']['facet_fields']['category'];
    
} else {
    // Manejar errores, por ejemplo, Solr no está disponible
    $resultados = [];
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Resultados de búsqueda</title>
    <link rel="stylesheet" href="../styles.css">
</head>

<body>
    <header>
        <a href="../index.html" class="regresar-btn">
            <img src="../images/goback.png" alt="Inicio" width="30" height="30">
            <span>Inicio</span>
        </a>
    </header>
    <main>
        <h1>Resultados de búsqueda para "
            <?php echo htmlspecialchars($query); ?>"
        </h1>
        <?php if (!empty($resultados)):?>
            <div id="facetacion">
                <?php for($i=0;$i<count($facetados);$i=$i+2): ?>
                    <a href='buscar.php?query=<?=$query?>&categoria_busqueda=<?=$facetados[$i]?>'>
                        <?php echo htmlspecialchars($facetados[$i])?>(<?php echo htmlspecialchars($facetados[$i+1])?>)
                    </a>&nbsp;
                <?php endfor;?>
            </div>
 <div class="resultados-container">
                <?php foreach ($resultados as $doc): ?>
                    <div class="resultado">
                        <h2>
                            <a href="<?php echo htmlspecialchars($doc['url']); ?>" target="_blank">
                                <?php echo htmlspecialchars(is_array($doc['title']) ? $doc['title'][0] : $doc['title']); ?>
                            </a>
                        </h2>
                        <p class="snippet">
                            <?php echo htmlspecialchars($doc['description']); ?>
                        </p>
                        <p class="categoria">
                        <p><strong>Categoria:</strong>
                            <?php echo htmlspecialchars($doc['category']); ?>
                        </p>
                        <p class="relevancia">
                        <p><strong>Relevancia:</strong>
                            <?php echo isset($doc['score']) ? round($doc['score'], 2) : 'N/A'; ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <h1>No se encontraron resultados.</h1>
        <?php 
            sugerirCorrec($query);
            endif; 
        ?>
    </main>
    <footer></footer>
</body>

</html>