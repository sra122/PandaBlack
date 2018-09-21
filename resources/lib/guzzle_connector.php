<?php

$client = new \GuzzleHttp\Client();


$res = $client->request(
    'POST',
    'https://pb.i-ways-network.org/api/oauth2/token',
    [
        'headers' => ['APP-ID' => 'Lr7u9w86bUL5qsg7MJEVut8XYsqrZmTTxM67qFdH89f4NYQnHrkgKkMAsH9YLE4tjce4GtPSqrYScSt7w558USrVgXHB'],
        'form_params' => [
            'grant_type' => 'authorization_code',
            'code' => SdkRestApi::getParam('auth_code')
        ]
    ]

);

/** @return array */
return json_decode($res->getBody(), true);