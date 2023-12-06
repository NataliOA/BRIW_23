<?php
    $q = $_GET['q'] ?? '';

    $palabras = explode(" ", $q);
    $res = [];
    $sugerencias= [];
    $sugs = '';

    foreach($palabras as $p) {
        $sugs .= $p.'+';
    }
    $sugs = substr($sugs, 0, -1);

    $count=0;
    $result = file_get_contents("https://api.datamuse.com/sug?v=es&s=$sugs");
    $res = json_decode($result, true);
    foreach($res as $ar){
        $sugerencias[$count]=$ar['word'];
        $count++;
    }

    if (!empty($res)) {
        foreach ($sugerencias as $sug) {
            echo "<li>" . htmlspecialchars($sug) . "</li>";
        }
    }
?>