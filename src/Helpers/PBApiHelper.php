<?php

namespace PandaBlack\Helpers;

use PandaBlack\Controllers\AppController;

class PBApiHelper extends SettingsHelper
{
    /**
     * @param $categoryId
     * @return mixed
     */
    public function fetchPBAttributes($categoryId)
    {
        $app = pluginApp(AppController::class);
        $attributeValueSet = $app->authenticate(self::ATTRIBUTES, $categoryId);
        if (isset($attributeValueSet)) {
            return $attributeValueSet;
        }
    }
}