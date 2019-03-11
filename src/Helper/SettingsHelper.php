<?php

use Plenty\Modules\Market\Settings\Contracts\SettingsRepositoryContract;
use Plenty\Modules\Market\Settings\Models\Settings;

class SettingsHelper
{
    /** @var SettingsRepositoryContract */
    protected $settingsRepositoryContract;
    /** @var Settings */
    protected $settingProperty;
    /** @var bool */
    protected $hasSettingProperty;

    public function __construct(SettingsRepositoryContract $settingsRepositoryContract)
    {
        $this->settingsRepositoryContract = $settingsRepositoryContract;
    }
    protected function getSettingProperty()
    {
        if ($this->hasSettingProperty === null) {
            /** @var Settings[] $properties */
            $properties = $this->settingsRepositoryContract->find('PandaBlack', 'property');

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
            $this->settingProperty = $this->settingsRepositoryContract->create('PandaBlack', 'property', [$key => $value]);
            $this->hasSettingProperty = true;
        } else {
            $this->settingProperty->settings[$key] = $value;
            $this->settingsRepositoryContract->update($this->settingProperty->settings, $this->settingProperty->id);
        }

    }

}