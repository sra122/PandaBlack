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
use Plenty\Modules\Property\Contracts\PropertyRepositoryContract;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;

class MappingController extends Controller
{
    public function mapping(Request $request)
    {
        $mappingInfos = $request->get('mappingInformation');
        $categoryId = $request->get('categoryId');

        return $this->checkPropertyValueExist(101, '1-99');

        /*foreach($mappingInfos as $key => $mappingInfo)
        {
            // Attribute as Property -- Create Automatically
            if(array_reverse(explode('-', $key))[0] == 'attribute' && $mappingInfo == 'Create Automatically') {

                $attributeName = str_replace('-attribute', '', $key);

                if(!($this->checkPropertyExist($attributeName))) {
                    return $this->createProperty($attributeName);
                }
            }

            // Attribute value as Property Value -- Create Automatically
            $attributeName = array_reverse(explode('~', $key))[0];

            $propertyId = $this->checkPropertyExist($attributeName);

            if(is_numeric($propertyId) && $mappingInfo == 'Create Automatically') {

            }
        }*/
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

       return $propertyRepo->getProperty($propertyId, ['options']);
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
}