<?php
/**
 * Created by PhpStorm.
 * User: sravan
 * Date: 13.06.19
 * Time: 12:07
 */

namespace PandaBlack\Controllers;

use PandaBlack\Helpers\PBApiHelper;
use PandaBlack\Helpers\SettingsHelper;
use Plenty\Modules\Property\Contracts\PropertyNameRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertyRelationRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertyRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertySelectionRepositoryContract;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;

class MappingController extends Controller
{
    public $mappingInfo = [];

    public function mapping(Request $request)
    {
        $this->fetchPropertiesInfo();

        $mappingInfos = $request->get('mappingInformation');
        $categoryId = $request->get('categoryId');

        $this->mapProperties($mappingInfos);

        $this->mapPropertyValues($mappingInfos, $categoryId);

        $this->saveMapping();
    }

    private function mapProperties($mappingInfos)
    {
        foreach($mappingInfos as $key => $mappingInfo)
        {
            // Attribute as Property -- Create Automatically
            if(array_reverse(explode('-', $key))[0] == 'attribute' && $mappingInfo == 'Create Automatically') {

                $attributeName = str_replace('-attribute', '', $key);

                if(!($this->checkPropertyExist($attributeName))) {
                    $this->createProperty($attributeName);

                    $this->mappingInfo['property'][$attributeName] =  $attributeName;
                }
            } else if(array_reverse(explode('-', $key))[0] == 'attribute') {

                $attributeName = str_replace('-attribute', '', $key);

                $this->mappingInfo['property'][$attributeName] = $mappingInfo;
            }
        }
    }


    private function mapPropertyValues($mappingInfos, $categoryId)
    {
        foreach($mappingInfos as $key => $mappingInfo)
        {
            // Attribute value as Property Value -- Create Automatically
            $attributeName = array_reverse(explode('~', $key))[0];

            $attributeValueName = explode('~', $key)[0];

            $propertyId = $this->checkPropertyExist($attributeName);

            if(is_numeric($propertyId) && $mappingInfo == 'Create Automatically') {
                if(!($this->checkPropertyValueExist($propertyId, $attributeValueName))) {
                    $selectionData = [
                        'propertyId' => $propertyId,
                        'relation' => [
                            [
                                'relationValues' => [
                                    [
                                        'value' => $attributeValueName,
                                        'lang' => 'de',
                                        'description' => $attributeValueName . '-PB-' . $categoryId
                                    ]
                                ]
                            ]
                        ]
                    ];

                    $propertySelectionRepo = pluginApp(PropertySelectionRepositoryContract::class);
                    $propertySelectionRepo->createPropertySelection($selectionData);

                    $this->mappingInfo['propertyValue'][$attributeValueName] = $attributeValueName;
                }
            } else if(is_numeric($propertyId)) {
                $this->mappingInfo['propertyValue'][$attributeValueName] = $mappingInfo;
            } else if(is_bool($propertyId)) {

                // If seller is trying to create a PropertyValue under a Property that is not Present.
                $settingsRepo = pluginApp(SettingsHelper::class);

                $notification = $settingsRepo->get(SettingsHelper::NOTIFICATION);
                $notification['propertyNotFound'][$attributeName] = $attributeValueName;
                $settingsRepo->set(SettingsHelper::NOTIFICATION, $notification);
            }
        }
    }


    private function saveMapping()
    {
        $settingsRepo = pluginApp(SettingsHelper::class);
        $settingsRepo->set(SettingsHelper::MAPPING_INFO, $this->mappingInfo);
    }


    private function createProperty($propertyName)
    {
        /** @var PropertyRepositoryContract $propertyRepository */
        $propertyRepository = pluginApp(PropertyRepositoryContract::class);

        /** @var PropertyNameRepositoryContract $propertyNameRepository */
        $propertyNameRepository = pluginApp(PropertyNameRepositoryContract::class);

        $propertyData = [
            'cast' => 'selection',
            'typeIdentifier' => 'item',
            'position' => 0,
            'names' => [
                [
                    'lang' => 'de',
                    'name' => $propertyName,
                    'description' => ''
                ]
            ]
        ];

        $property = $propertyRepository->createProperty($propertyData);

        foreach($propertyData['names'] as $propertyName) {
            $propertyName['propertyId'] = $property->id;
            $propertyName = $propertyNameRepository->createName($propertyName);
        }

        return $property;
    }


    private function checkPropertyExist($propertyName)
    {
        /** @var PropertyNameRepositoryContract $propertyNameRepository */
        $propertyNameRepository = pluginApp(PropertyNameRepositoryContract::class);

        $properties = $propertyNameRepository->listNames();

        foreach($properties as $property)
        {
            if($property->name === $propertyName) {
                return $property->propertyId;
            }
        }

        return false;
    }


    private function checkPropertyValueExist($propertyId, $propertyValue)
    {
       $propertyRepo = pluginApp(PropertyRepositoryContract::class);
       $propertyRelationRepo = pluginApp(PropertyRelationRepositoryContract::class);

       $propertyRelations = $propertyRepo->getProperty($propertyId, ['relation']);

       foreach($propertyRelations->relation as $propertyRelation)
       {
            $propertyRelationData = $propertyRelationRepo->getRelation($propertyRelation->id);

            foreach($propertyRelationData->relationValues as $propertyRelationValue)
            {
                if($propertyRelationValue->lang == 'de' && ($propertyRelationValue->value == $propertyValue)) {
                    return true;
                }
            }
       }

       return false;
    }


    public function fetchPropertiesInfo()
    {
        $settingsHelper = pluginApp(SettingsHelper::class);

        $this->mappingInfo = $settingsHelper->get(SettingsHelper::MAPPING_INFO);

        if(empty($this->mappingInfo)) {
            $settingsHelper->set(SettingsHelper::MAPPING_INFO, []);
        }

        return $this->mappingInfo;
    }


    /** TODO */
    private function propertyUnchanged($attributeName, $categoryId)
    {
        /** @var SettingsHelper $settingHelper */
        $settingHelper = pluginApp(SettingsHelper::class);

        $pbApiHelper = pluginApp(PBApiHelper::class);

        $attributes = $pbApiHelper->fetchPBAttributes($categoryId);

        foreach($attributes as $attribute)
        {
            return $attribute->name;
        }

        /*$attributes = $settingHelper->get(SettingsHelper::ATTRIBUTES);

        if(isset($attributes[$categoryId])) {
            foreach($attributes[$categoryId] as $attribute) {
<<<<<<< HEAD
                return $attribute->name;
=======
                if(is_object($attribute)) {
                    return 'object';
                }
>>>>>>> 2e14752e78d3672c288ffcfe4c966f8d01b8c7f9
            }
        }*/

        return false;
    }


    public function fetchNotifications()
    {
        $settingsHelper = pluginApp(SettingsHelper::class);
        return $settingsHelper->get(SettingsHelper::NOTIFICATION);
    }
}