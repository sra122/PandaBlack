<?php

namespace PandaBlack\Helpers;

use Plenty\Modules\Market\Settings\Contracts\SettingsRepositoryContract;
use Plenty\Modules\Market\Settings\Models\Settings;

class SettingsHelper
{
    /** @var SettingsRepositoryContract */
    protected $SettingsRepositoryContract;
    /** @var Settings */
    protected $settingProperty;
    /** @var bool */
    protected $hasSettingProperty;

    public function __construct(SettingsRepositoryContract $SettingsRepositoryContract)
    {
        $this->SettingsRepositoryContract = $SettingsRepositoryContract;
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

}