<?php

namespace PandaBlack\Controllers;

use Plenty\Modules\Item\Attribute\Contracts\AttributeRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertySelectionRepositoryContract;
use Plenty\Plugin\Controller;
use Plenty\Modules\Item\Attribute\Contracts\AttributeValueRepositoryContract;
use Plenty\Modules\Market\Settings\Contracts\SettingsRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertyRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertyNameRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertyRelationRepositoryContract;

//use Plenty\Modules\Item\Property\Contracts\PropertyRepositoryContract
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
        $propertyRepo = pluginApp(PropertyRepositoryContract::class);
        $propertyList = $propertyRepo->listProperties(1, 50, [], [], 0);

        return $propertyList;
    }
}