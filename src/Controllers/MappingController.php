<?php
/**
 * Created by PhpStorm.
 * User: sravan
 * Date: 13.06.19
 * Time: 12:07
 */

namespace PandaBlack\Controllers;

use PandaBlack\Helpers\SettingsHelper;
use Plenty\Modules\Property\Contracts\PropertyNameRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertyRepositoryContract;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;

class MappingController extends Controller
{
    public function mapping(Request $request)
    {
        $mappingInfos = $request->get('mappingInformation');
        $categoryId = $request->get('categoryId');

        foreach($mappingInfos as $key => $mappingInfo)
        {
            if(array_reverse(explode('-', $key))[0] == 'attribute' && $mappingInfo == 'Create Automatically') {

                $attributeName = str_replace('-attribute', '', $key);

                if(!($this->checkPropertyExist($attributeName)) && ($this->propertyUnchanged($attributeName, (int)$categoryId))) {
                    $this->createProperty($attributeName);
                }

            }
        }
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
    }


    private function checkPropertyExist($propertyName)
    {
        /** @var PropertyNameRepositoryContract $propertyNameRepository */
        $propertyNameRepository = pluginApp(PropertyNameRepositoryContract::class);

        $properties = $propertyNameRepository->listNames();

        foreach($properties as $property)
        {
            if($property->name === $propertyName) {
                return true;
            }
        }

        return false;
    }


    private function propertyUnchanged($attributeName, $categoryId)
    {
        /** @var SettingsHelper $settingHelper */
        $settingHelper = pluginApp(SettingsHelper::class);

        $attributes = $settingHelper->get(SettingsHelper::ATTRIBUTES);

        if(isset($attributes[$categoryId])) {
            foreach($attributes[$categoryId] as $attribute) {
                if($attribute->required && ($attribute->name == $attributeName)) {
                    return true;
                }
            }
        }

        return false;
    }
}