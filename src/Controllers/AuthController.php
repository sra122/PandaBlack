<?php

namespace PandaBlack\Controllers;

use PandaBlack\Helpers\SettingsHelper;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Modules\System\Models\WebstoreConfiguration;
use Plenty\Modules\Helper\Services\WebstoreHelper;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Modules\Order\Referrer\Contracts\OrderReferrerRepositoryContract;

/**
 * Class AuthController
 * @package PandaBlack\Controllers
 */
class AuthController extends Controller
{
    /** @var SettingsHelper */
    protected $Settings;

    public function __construct(SettingsHelper $SettingsHelper)
    {
        $this->Settings = $SettingsHelper;
    }

    /**
     * @param WebstoreHelper $webstoreHelper
     * @return array
     */
    public function getLoginUrl(WebstoreHelper $webstoreHelper)
    {
        /** @var WebstoreConfiguration $webstore */
        $webstore = $webstoreHelper->getCurrentWebstoreConfiguration();

        return [
            'loginUrl' => $webstore->domainSsl . '/markets/panda-black/auth/authentication',
        ];
    }

    public function getAuthentication(Request $request, LibraryCallContract $libCall)
    {
        if($this->sessionCheck()) {
            $tokenInformation = $libCall->call(
                'PandaBlack::pandaBlack_authentication', ['auth_code' => $request->get('autorize_code')]
            );
            $this->tokenStorage($tokenInformation);

            return 'Login was successful. This window will close automatically.<script>window.close();</script>';
        }

        return 'Your session expired, please close this window and try again.';
    }

    /**
     * Saving token information.
     *
     * @param $tokenInformation
     */
    public function tokenStorage($tokenInformation)
    {
        $tokenInformation['Response']['expires_in'] = time() + $tokenInformation['Response']['expires_in'];
        $tokenInformation['Response']['refresh_token_expires_in'] = time() + $tokenInformation['Response']['refresh_token_expires_in'];

        $this->Settings->set('pbToken', $tokenInformation['Response']);
    }

    public function sessionCreation()
    {
        $this->Settings->set('sessionTime', time());
    }

    /**
     * @return bool
     */
    public function sessionCheck()
    {
        $sessionTime = $this->Settings->get('sessionTime');

        return $sessionTime !== null && (time() - $sessionTime) < 600;
    }

    /**
     * @return string
     */
    public function tokenExpireTime()
    {
        $tokenData = $this->Settings->get('pbToken');

        if ($tokenData === null || !isset($tokenData['expires_in'])) {
            return null;
        }

        return $tokenData['expires_in'];
    }
}