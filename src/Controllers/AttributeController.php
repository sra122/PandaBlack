<?php

namespace PandaBlack\Controllers;

use Plenty\Modules\Item\Attribute\Contracts\AttributeRepositoryContract;
use Plenty\Plugin\Controller;
use Plenty\Modules\Item\Attribute\Contracts\AttributeValueRepositoryContract;
use Plenty\Modules\Market\Settings\Contracts\SettingsRepositoryContract;


use Plenty\Modules\Property\Contracts\PropertyRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertyNameRepositoryContract;
class AttributeController extends Controller
{
    public function createPBAttributes($categoryId = null)
    {
        $app = pluginApp(AppController::class);
        $attributeValueSets = $app->authenticate('pandaBlack_attributes', 65);

        if(!empty($attributeValueSets)) {
            foreach($attributeValueSets as $key => $attributeValueSet)
            {
                /*$attributeRepo = pluginApp(AttributeRepositoryContract::class);
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
                }*/

                $propertyRepository = pluginApp(PropertyRepositoryContract::class);
                $propertyNameRepository = pluginApp(PropertyNameRepositoryContract::class);

                $attributeData = [
                    'cast' => 'selection',
                    'typeIdentifier' => 'item',
                    'position' => 0,
                    'names' => [
                        [
                            'lang' => 'de',
                            'name' => $attributeValueSet['name'] . '-PB-' . $key
                        ]
                    ]
                ];

                $propertyNames = $propertyNameRepository->listNames();

                return $propertyNames;

                /*$property = $propertyRepository->createProperty($attributeData);

                try {
                    foreach($attributeData['names'] as $name) {
                        $name['propertyId'] = $property->id;
                        $propertyName = $propertyNameRepository->createName($name);
                    }

                } catch(\Exception $e) {

                }*/
            }
        }
    }


    public function getPBAttributes($categoryId)
    {
        $app = pluginApp(AppController::class);
        $attributeValueSet = $app->authenticate('pandaBlack_attributes', $categoryId);

        if(isset($attributeValueSet)) {
            return $attributeValueSet;
        }
    }


    public function deletePBProperties()
    {
        $settingRepo = pluginApp(SettingsRepositoryContract::class);
        $settingRepo->deleteAll('PandaBlack', 'property');
    }
}