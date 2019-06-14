<?php

namespace PandaBlack\Controllers;

use PandaBlack\Helpers\PBApiHelper;
use PandaBlack\Helpers\SettingsHelper;
use Plenty\Modules\Item\Attribute\Contracts\AttributeRepositoryContract;
use Plenty\Plugin\Controller;
use Plenty\Modules\Item\Attribute\Contracts\AttributeValueRepositoryContract;
use Plenty\Modules\Market\Settings\Contracts\SettingsRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertyRepositoryContract;
class AttributeController extends Controller
{
    public function createPBAttributes($categoryId)
    {
        $app = pluginApp(AppController::class);
        $attributeValueSets = $app->authenticate('pandaBlack_attributes', $categoryId);
        if(!empty($attributeValueSets)) {
            foreach($attributeValueSets as $key => $attributeValueSet)
            {
                $attributeRepo = pluginApp(AttributeRepositoryContract::class);
                $attributeValueRepository = pluginApp(AttributeValueRepositoryContract::class);
                $attributeCheck = $attributeRepo->findByBackendName($attributeValueSet['name'] . '-PB-' . $key);
                if(empty($attributeCheck) && !empty($attributeValueSet['values']) && $attributeValueSet['required']) {
                    $attributeValueMap = [
                        'backendName' => $attributeValueSet['name'] . '-PB-' . $key,
                    ];
                    $attributeInfo = $attributeRepo->create($attributeValueMap)->toArray();
                    foreach($attributeValueSet['values'] as $attributeKey => $attributeValue) {
                        $attributeValueRepository->create(['backendName' => trim($attributeValue . '-PB-' . $attributeKey)], $attributeInfo['id']);
                    }
                }
            }
        }
    }


    public function getPBAttributes($categoryId)
    {
        $settingsHelper = pluginApp(SettingsHelper::class);
        $pbApiHelper = pluginApp(PBApiHelper::class);

        $attributes = $settingsHelper->get(SettingsHelper::ATTRIBUTES);

        if(isset($attributes[$categoryId])) {
            return $attributes[$categoryId];
        } else {
            $attributes[$categoryId] = $pbApiHelper->fetchPBAttributes($categoryId);
            $settingsHelper->set(SettingsHelper::ATTRIBUTES, $attributes);
        }
    }

    public function deletePBProperties()
    {
        $settingRepo = pluginApp(SettingsRepositoryContract::class);
        $settingRepo->deleteAll('PandaBlack', 'property');
    }


    public function getPMProperties()
    {
        $settingsHelper = pluginApp(SettingsHelper::class);
        $propertyRepo = pluginApp(PropertyRepositoryContract::class);

        //Pagination is 0, it will provide complete list of Data.
        $properties = $propertyRepo->listProperties(1, 50, [], [], 0);

        return $properties;

        /*$propertiesList = [];
        $lang = ['de', 'DE', 'De'];
        $key = 0;

        foreach($properties as $property)
        {
            if(!empty($property['names']) && ($property['id'] !== $settingsHelper->get(SettingsHelper::CATEGORIES_AS_PROPERTIES))) {
                foreach($property['names'] as $propertyName)
                {
                    if(in_array($propertyName['lang'], $lang) && !empty($propertyName['name'])) {
                        $propertiesList[$key++] = $propertyName['name'];
                    }
                }
            }
        }

        return $propertiesList;*/
    }


    public function getPMPropertyValues()
    {
        $settingsHelper = pluginApp(SettingsHelper::class);
        $propertyRepo = pluginApp(PropertyRepositoryContract::class);

        //Pagination is 0, it will provide complete list of Data.
        $properties = $propertyRepo->listProperties(1, 50, [], [], 0);
        $propertyValues = [];
        $lang = ['de', 'DE', 'De'];
        $key = 0;

        foreach($properties as $property)
        {
            if(!empty($property['selections']) && ($property['id'] !== $settingsHelper->get(SettingsHelper::CATEGORIES_AS_PROPERTIES))) {
                foreach($property['selections'] as $selectionProperty) {
                    $propertyValue = $selectionProperty['relation']['relationValues'][0];

                    if(in_array($propertyValue['lang'], $lang) && !empty($propertyValue['value'])) {
                        $propertyValues[$key++] = $propertyValue['value'];
                    }
                }
            }
        }

        return $propertyValues;
    }
}