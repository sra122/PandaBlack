<?php

$client = new \GuzzleHttp\Client();

$res = $client->request(
    'POST',
    'https://pb.i-ways-network.org/api/order/' . SdkRestApi::getParam('reference_id'),
    [
        'headers' => [
            'APP-ID' => 'Lr7u9w86bUL5qsg7MJEVut8XYsqrZmTTxM67qFdH89f4NYQnHrkgKkMAsH9YLE4tjce4GtPSqrYScSt7w558USrVgXHB',
            'API-AUTH-TOKEN' => SdkRestApi::getParam('token')
        ],
        'form_params' => [
            'order_details' => SdkRestApi::getParam('order_details')
        ]
    ]
);

/** @return array */
return json_decode($res->getBody(), true);