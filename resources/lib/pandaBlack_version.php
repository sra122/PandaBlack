<?php
$client = new \GuzzleHttp\Client();
$res = $client->request(
    'POST',
    'https://pb.i-ways-network.org/api/plugin-version',
    [
        'headers' => [
            'APP-ID' => 'Lr7u9w86bUL5qsg7MJEVut8XYsqrZmTTxM67qFdH89f4NYQnHrkgKkMAsH9YLE4tjce4GtPSqrYScSt7w558USrVgXHB',
            'API-AUTH-TOKEN' => SdkRestApi::getParam('token')
        ],
        'form_params' => [
            'version' => '1.0.14'
        ]
    ]
);
/** @return array */
return json_decode($res->getBody(), true);