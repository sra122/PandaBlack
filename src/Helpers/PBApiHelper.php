<?php

namespace PandaBlack\Helpers;

use PandaBlack\Controllers\AppController;

class PBApiHelper extends SettingsHelper
{
    const ORDER_CREATED = 'orderCreated';
    const ORDER_ERROR = 'orderError';
    const PRODUCT_EXTRACTION_ERROR = 'productExtractionError';
    const REFERRER_ID_ERROR = 'referrerIdentifierError';

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