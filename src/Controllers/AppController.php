<?php

namespace PandaBlack\Controllers;

use PandaBlack\Helpers\SettingsHelper;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Plugin\Controller;
use Plenty\Modules\Market\Settings\Contracts\SettingsRepositoryContract;
use Plenty\Plugin\CachingRepository;
class AppController extends Controller
{
    /** @var SettingsHelper */
    protected $Settings;

    public function __construct(SettingsHelper $SettingsHelper)
    {
        $this->Settings = $SettingsHelper;
    }

    public function authenticate($apiCall, $params = null, $productDetails = null)
    {
        $libCall = pluginApp(LibraryCallContract::class);
        $token = $this->Settings->getCredential('pbToken');

        if ($token !== null) {
            if ($token['expires_in'] > time()) {
                $response = $libCall->call(
                    'PandaBlack::'. $apiCall,
                    [
                        'token' => $token['token'],
                        'category_id' => $params,
                        'product_details' => $productDetails
                    ]
                );
            } else if($token['refresh_token_expires_in'] > time()) {
                $response = $libCall->call(
                    'PandaBlack::pandaBlack_categories',
                    [
                        'token' => $token['refresh_token'],
                        'category_id' => $params,
                        'product_details' => $productDetails
                    ]
                );
            }

            if (\is_array($response) && isset($response['Response'])) {
                return $response['Response'];
            }
        }
    }
}