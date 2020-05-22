<?php

namespace PandaBlack\Controllers;

use PandaBlack\Helpers\SettingsHelper;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Plugin\Controller;
class AppController extends Controller
{
    /** @var SettingsHelper */
    protected $Settings;

    public function __construct(SettingsHelper $SettingsHelper)
    {
        $this->Settings = $SettingsHelper;
    }

    public function authenticate($apiCall, $params = null, $productDetails = null, $orderDetails = null)
    {
        $libCall = pluginApp(LibraryCallContract::class);
        $token = $this->Settings->get('pbToken');

        if ($token !== null) {
            if ($token['expires_in'] > time()) {
                $response = $libCall->call(
                    'PandaBlack::'. $apiCall,
                    [
                        'token' => $token['token'],
                        'category_id' => $params,
                        'product_details' => $productDetails,
                        'order_details' => $orderDetails
                    ]
                );
            } else if($token['refresh_token_expires_in'] > time()) {
                $response = $libCall->call(
                    'PandaBlack::pandaBlack_categories',
                    [
                        'token' => $token['refresh_token'],
                        'category_id' => $params,
                        'product_details' => $productDetails,
                        'order_details' => $orderDetails
                    ]
                );
            }

            if (\is_array($response) && isset($response['Response'])) {
                return $response['Response'];
            }
        }
    }


    public function shippingNotification($trackingNumber = null, $carrier = null, $referenceId = null)
    {
        $libCall = pluginApp(LibraryCallContract::class);
        $token = $this->Settings->get('pbToken');
        if ($token !== null) {
            if ($token['expires_in'] > time()) {
                $response = $libCall->call(
                    'PandaBlack::pandaBlack_order_shipping',
                    [
                        'token' => $token['token'],
                        'tracking_number' => $trackingNumber,
                        'carrier' => $carrier,
                        'reference_id' => $referenceId
                    ]
                );
            } else {
                if ($token['refresh_token_expires_in'] > time()) {
                    $response = $libCall->call(
                        'PandaBlack::pandaBlack_order_shipping',
                        [
                            'token' => $token['token'],
                            'tracking_number' => $trackingNumber,
                            'carrier' => $carrier,
                            'reference_id' => $referenceId
                        ]
                    );
                }
            }
            if (\is_array($response) && isset($response['Response'])) {
                return $response['Response'];
            }
        }
    }
}