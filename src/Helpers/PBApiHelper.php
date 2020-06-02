<?php

namespace PandaBlack\Helpers;

use PandaBlack\Controllers\AppController;

class PBApiHelper extends SettingsHelper
{
    const ORDER_CREATE = 'orderCreate';
    const ORDER_ERROR = 'orderError';
    const PRODUCT_EXTRACTION_ERROR = 'productExtractionError';
    const REFERRER_ID_ERROR = 'referrerIdentifierError';
    const PRODUCT_EXPORT = 'product_export';
    const CREATE_CONTACT = 'createContact';
    const CONTACT_CREATION_ERROR = 'contactCreationError';

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