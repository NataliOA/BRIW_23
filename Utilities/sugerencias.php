<?php
    $q = $_GET['q'] ?? '';
    $api_key = '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';

    function getSuggestions($input) {
        global $api_key;
        $url = file_get_contents('APIKey.php');
        $data = array(
            'prompt' => $input, 
            'max_tokens' => 10
        );

        $options = array(
            'http' => array(
                'header'  => "Content-type: application/json\r\n" .
                            "Authorization: Bearer $api_key\r\n",
                'method'  => 'POST',
                'content' => json_encode($data)
            )
        );

        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if ($result === FALSE) { /* Manejar error */ }

        $sugerencia =  json_decode($result, true);
    }

?>