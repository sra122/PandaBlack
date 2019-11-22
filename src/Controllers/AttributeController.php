<?php

namespace PandaBlack\Controllers;

use PandaBlack\Helpers\PBApiHelper;
use PandaBlack\Helpers\SettingsHelper;
use PandaBlack\Repositories\AttributesRepository;
use PandaBlack\Repositories\AttributeValuesRepository;
use Plenty\Modules\Item\Attribute\Contracts\AttributeRepositoryContract;
use Plenty\Plugin\Controller;
use Plenty\Modules\Item\Attribute\Contracts\AttributeValueRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertyRepositoryContract;
class AttributeController extends Controller
{
    /**
     * CreatePMAttributes Method
     *
     * @param $categoryId
     */
    public function createPMAttributes($categoryId)
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


    /**
     * GetPBAttributes Method
     *
     * @param $categoryId
     * @return mixed
     */
    public function getPBAttributes($categoryId)
    {
        $pbApiHelper = pluginApp(PBApiHelper::class);
        $attributesRepo = pluginApp(AttributesRepository::class);
        $attributeValueRepo = pluginApp(AttributeValuesRepository::class);

        $attributes = $attributesRepo->getAttributeForCategory($categoryId);
        $attributesInfo = [];
        if(count($attributes) <= 0) {
            $attributes = $pbApiHelper->fetchPBAttributes($categoryId);
            foreach($attributes as $attributeId => $attribute)
            {
                if($attribute['required'] && !($attribute['is_deleted'])) {
                    $attributeData = [
                        'categoryId' => (int)$categoryId,
                        'attributeId' => (int)$attributeId,
                        'attributeName' => $attribute['name']
                    ];
                    $attributesRepo->createAttribute($attributeData);

                    $values = [];

                    foreach($attribute['values'] as $attributeValueIdentifier => $attributeValue)
                    {
                        if(!($attributeValue['is_deleted'])) {
                            $attributeValueData = [
                                'categoryId' => (int)$categoryId,
                                'attributeId' => (int)$attributeId,
                                'attributeValueName' => $attributeValue['name'],
                                'attributeValueId' => (int)$attributeValueIdentifier
                            ];

                            $attributeValueRepo->createAttributeValue($attributeValueData);
                            $values[(int)$attributeValueIdentifier] = $attributeValue;
                        }
                    }

                    $attributesInfo[(int)$attributeId] = [
                        'categoryId' => $categoryId,
                        'name' => $attribute['name'],
                        'values' => $values
                    ];
                }
            }
        } else {
            foreach($attributes as $attribute)
            {
                $values = [];
                $attributeValues = $attributeValueRepo->getAttributeValuesForAttribute($attribute->attribute_identifier);

                foreach($attributeValues as $attributeValue)
                {
                    $values[$attributeValue->attribute_value_identifier] = $attributeValue->name;
                }

                $attributesInfo[$attribute->attribute_identifier] = [
                    'categoryId' => $categoryId,
                    'name' => $attribute->name,
                    'values' => $values
                ];
            }
        }

        return $attributesInfo;
    }

    /**
     * GetPMProperties Method
     *
     * @return array
     */
    public function getPMProperties()
    {
        $settingsHelper = pluginApp(SettingsHelper::class);
        $propertyRepo = pluginApp(PropertyRepositoryContract::class);

        //Pagination is 0, it will provide complete list of Data.
        $properties = $propertyRepo->listProperties(1, 50, [], [], 0);

        $propertiesList = [];
        $lang = ['de', 'DE', 'De'];

        foreach($properties as $property)
        {
            if(!empty($property['names']) && ($property['id'] !== $settingsHelper->get(SettingsHelper::CATEGORIES_AS_PROPERTIES))) {
                foreach($property['names'] as $propertyName)
                {
                    if(in_array($propertyName['lang'], $lang) && !empty($propertyName['name'])) {
                        $propertiesList[$propertyName['propertyId']] = $propertyName['name'];
                    }
                }
            }
        }

        natcasesort($propertiesList);

        return array_values($propertiesList);
    }


    /**
     * GetPMPropertyValues Method
     *
     * @return array
     */
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

                $propertyName = '';

                foreach($property['names'] as $propertyName)
                {
                    if(in_array($propertyName['lang'], $lang)) {
                        $propertyName = $propertyName['name'];
                    }
                }

                foreach($property['selections'] as $selectionProperty) {
                    $propertyValue = $selectionProperty['relation']['relationValues'][0];

                    if(in_array($propertyValue['lang'], $lang) && !empty($propertyValue['value'])) {
                        $propertyValues[$key++] = $propertyValue['value'] . '§' . $propertyName;
                    }
                }
            }
        }

        natcasesort($propertyValues);

        return $propertyValues;
    }
}