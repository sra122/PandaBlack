<?php

namespace PandaBlack\Helpers;

use Plenty\Modules\Market\Credentials\Contracts\CredentialsRepositoryContract;
use Plenty\Modules\Market\Credentials\Models\Credentials;
use Plenty\Modules\Market\Settings\Contracts\SettingsRepositoryContract;
use Plenty\Modules\Market\Settings\Models\Settings;

class SettingsHelper
{
    /** @var SettingsRepositoryContract */
    protected $SettingsRepositoryContract;
    /** @var CredentialsRepositoryContract */
    protected $CredentialsRepositoryContract;

    /** @var Settings */
    protected $settingProperty;
    /** @var bool */
    protected $hasSettingProperty;

    /** @var Credentials */
    protected $credentialProperty;
    /** @var bool */
    protected $hasCredentialProperty;

    public function __construct(SettingsRepositoryContract $SettingsRepositoryContract, CredentialsRepositoryContract $CredentialsRepositoryContract)
    {
        $this->SettingsRepositoryContract = $SettingsRepositoryContract;
        $this->CredentialsRepositoryContract = $CredentialsRepositoryContract;
    }

    protected function getSettingProperty()
    {
        if ($this->hasSettingProperty === null) {
            /** @var Settings[] $properties */
            $properties = $this->SettingsRepositoryContract->find('PandaBlack', 'property');

            if (empty($properties)) {
                $this->hasSettingProperty = false;
            } else {
                $this->hasSettingProperty = true;
                $this->settingProperty = $properties[0];
            }
        }

        return $this->settingProperty;
    }

    protected function getCredentialProperty()
    {
        if ($this->hasCredentialProperty === null) {
            $credentialsId = $this->get('pb_credentials_id');
            if ($credentialsId === null) {
                $this->hasCredentialProperty = false;
                return null;
            }

            try {
                $this->credentialProperty = $this->CredentialsRepositoryContract->get($credentialsId);
                $this->hasCredentialProperty = true;
            } catch (\Exception $e) {
                $this->hasCredentialProperty = false;
                return null;
            }
        }

        return $this->credentialProperty;
    }

    public function get($key)
    {
        $settingProperty = $this->getSettingProperty();
        if ($settingProperty === null || !isset($settingProperty->settings[$key])) {
            return null;
        }

        return $settingProperty->settings[$key];
    }

    public function set($key, $value)
    {
        $settingProperty = $this->getSettingProperty();
        if ($settingProperty === null) {
            $this->settingProperty = $this->SettingsRepositoryContract->create('PandaBlack', 'property', [$key => $value]);
            $this->hasSettingProperty = true;
        } else {
            $this->SettingsRepositoryContract->update(array_merge($this->settingProperty->settings, [$key => $value]), $this->settingProperty->id);
            $this->hasSettingProperty = null;
            $this->settingProperty = null;
        }
    }

    public function getCredential($key)
    {
        $credentialProperty = $this->getCredentialProperty();
        if ($credentialProperty !== null && isset($credentialProperty[$key])) {
            return $credentialProperty[$key];
        }

        return null;
    }

    public function setCredential($key, $value)
    {
        $credentialProperty = $this->getCredentialProperty();
        if ($credentialProperty === false) {
            $this->credentialProperty = $this->CredentialsRepositoryContract->create([$key => $value]);
            $this->set('pb_credentials_id', $this->credentialProperty->id);
            $this->hasCredentialProperty = true;
        } else {
            $this->CredentialsRepositoryContract->update($this->get('pb_credentials_id'), array_merge($this->credentialProperty->data, [$key => $value]));
        }
    }

}