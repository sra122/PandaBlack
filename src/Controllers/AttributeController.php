<?php

namespace PandaBlack\Controllers;

use PandaBlack\Helpers\SettingsHelper;
use Plenty\Modules\Item\Attribute\Contracts\AttributeRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertySelectionRepositoryContract;
use Plenty\Plugin\Controller;
use Plenty\Modules\Item\Attribute\Contracts\AttributeValueRepositoryContract;
use Plenty\Modules\Market\Settings\Contracts\SettingsRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertyRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertyNameRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertyRelationRepositoryContract;
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
        $app = pluginApp(AppController::class);
        $attributeValueSet = $app->authenticate('pandaBlack_attributes', $categoryId);
        if (isset($attributeValueSet)) {
            return $attributeValueSet;
        }
    }


    public function deletePBProperties()
    {
        $settingRepo = pluginApp(SettingsRepositoryContract::class);
        $settingRepo->deleteAll('PandaBlack', 'property');
    }


    public function getPMProperties()
    {
        $settingHelper = pluginApp(SettingsHelper::class);
        $propertyRepo = pluginApp(PropertyRepositoryContract::class);

        //Pagination is 0, it will provide complete list of Data.
        $properties = $propertyRepo->listProperties(1, 50, [], [], 0);
        $propertiesList = [];
        $lang = ['de', 'DE', 'De'];
        $key = 0;

        foreach($properties as $property)
        {
            if(!empty($property['names']) && ($property['id'] !== $settingHelper->get('panda_black_category_as_property'))) {
                foreach($property['names'] as $propertyName)
                {
                    if(in_array($propertyName['lang'], $lang) && !empty($propertyName['name'])) {
                        $propertiesList[$key++] = $propertyName['name'];
                    }
                }
            }
        }

        return $propertiesList;
    }


    public function getPMPropertyValues()
    {
        $settingHelper = pluginApp(SettingsHelper::class);
        $propertyRepo = pluginApp(PropertyRepositoryContract::class);

        //Pagination is 0, it will provide complete list of Data.
        $properties = $propertyRepo->listProperties(1, 50, [], [], 0);
        $propertyValues = [];
        $lang = ['de', 'DE', 'De'];
        $key = 0;

        foreach($properties as $property)
        {
            if(!empty($property['selections']) && ($property['id'] !== $settingHelper->get('panda_black_category_as_property'))) {
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