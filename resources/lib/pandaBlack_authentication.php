<?php

$client = new \GuzzleHttp\Client();

$path = getcwd();
$file = $path . '../../plugin.json';

$version = json_decode(file_get_contents($file), true)['version'];

$res = $client->request(
    'POST',
    'https://pb.i-ways-network.org/api/oauth2/token',
    [
        'headers' => [
            'APP-ID' => 'Lr7u9w86bUL5qsg7MJEVut8XYsqrZmTTxM67qFdH89f4NYQnHrkgKkMAsH9YLE4tjce4GtPSqrYScSt7w558USrVgXHB',
            'VERSION' => $version
        ],
        'form_params' => [
            'grant_type' => 'authorization_code',
            'code' => SdkRestApi::getParam('auth_code')
        ]
    ]

);

/** @return array */
return json_decode($res->getBody(), true);