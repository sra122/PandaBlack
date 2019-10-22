<?php

$client = new \GuzzleHttp\Client();

$path = getcwd();
$file = $path . '../../plugin.json';

$version = json_decode(file_get_contents($file), true)['version'];

$res = $client->request(
    'GET',
    'https://pb.i-ways-network.org/notification-api/notifications',
    [
        'headers' => [
            'APP-ID' => 'Lr7u9w86bUL5qsg7MJEVut8XYsqrZmTTxM67qFdH89f4NYQnHrkgKkMAsH9YLE4tjce4GtPSqrYScSt7w558USrVgXHB',
            'API-AUTH-TOKEN' => SdkRestApi::getParam('token'),
            'VERSION' => $version
        ]
    ]

);

/** @return array */
return json_decode($res->getBody(), true);