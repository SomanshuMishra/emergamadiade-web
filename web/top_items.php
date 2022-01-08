<?php

function get_content($URL)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $URL);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function results($subreddit, $size)
{
    $base_url = "http://127.0.0.1:8080/";
    $data = get_content(base_url+"feeds/all/$subreddit/$size");
    return json_decode($data, true);
}

function getQueryParameter($url, $param)
{
    $parsedUrl = parse_url($url);
    if (array_key_exists('query', $parsedUrl)) {
        parse_str($parsedUrl['query'], $queryParameters);
        if (array_key_exists($param, $queryParameters)) {
            return $queryParameters[$param];
        }
    }
}

$subreddit = $_GET['subreddit'];
$size = 9999;
echo count(results($subreddit, $size)['assets']);

//$return_arr = [];

foreach (results($subreddit, $size)['assets'] as $result) {
    if ($result['w2c_link'] != null) {
        $url = urldecode($result['w2c_link']);

        echo $url . "</br></br>";
        /*        if (stripos($url, 'taobao') !== false) {
                    $query_string = 'id';
                    $base_url = "https://item.taobao.com/item.htm?id=";
                } elseif (stripos($url, 'weidian') !== false) {
                    $query_string = 'itemID';
                    $base_url = "https://weidian.com/item.html?itemID=";
                } else {
                    $query_string = '/';
                    $base_url = $url;
                }

                $result = getQueryParameter($url, $query_string);
                $return_arr[] = $base_url . $result;*/
    }
}

/*$return_arr = array_count_values($return_arr);
arsort($return_arr);*/

?>
<html>
<head>

</head>
<body>

<?php
/*
foreach ($return_arr as $key => $value) {
    echo "<a href='$key' target='_blank' rel='noreferrer'>$key ($value)</a><br>";
}

*/ ?>

</body>
</html>
