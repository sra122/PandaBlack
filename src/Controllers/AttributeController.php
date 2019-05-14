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
class AttributeController extends Controller
{
    public function createPBAttributes($categoryId = null)
    {
        $app = pluginApp(AppController::class);
        //$attributeValueSets = $app->authenticate('pandaBlack_attributes', 65);

        $propertyRepository = pluginApp(PropertyRepositoryContract::class);

        $attributeData = [
            'cast' => 'selection',
            'typeIdentifier' => 'item',
            'position' => 0,
            'names' => [
                [
                    'lang' => 'de',
                    'name' => 'Sprache'
                ],
                [
                    'lang' => 'en',
                    'name' => 'Language'
                ]
            ]
        ];

        $property = $propertyRepository->createProperty($attributeData);


        $dropdownValue = [
            'propertyId' => $property->id,
            'relation' => [
                0 => [
                    'relationValues' => [
                        0 => [
                            'value' => 'english',
                            'lang' =>'EN',
                            'description' => 'english description'
                        ],
                        1 => [
                            'value' => 'german',
                            'lang' => 'DE',
                            'description' => 'german description'
                        ]
                    ]
                ]
            ]
        ];


        $propertySelectionRepo = pluginApp(PropertySelectionRepositoryContract::class);
        $propertySelection = $propertySelectionRepo->createPropertySelection($dropdownValue);



            /*foreach($attributeValueSets as $key => $attributeValueSet)
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

                /*if(empty($attributeCheck) && !empty($attributeValueSet['values']) && $attributeValueSet['required']) {

                    $propertyRepository = pluginApp(PropertyRepositoryContract::class);
                    $propertyNameRepository = pluginApp(PropertyNameRepositoryContract::class);
                    $propertyRelationRepository = pluginApp(PropertyRelationRepositoryContract::class);
                    $propertyNameMatched = false;
                    $propertyId = '';

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

                    if(!empty($propertyNames)) {
                        foreach($propertyNames as $propertyName) {
                            if($propertyName->name === $attributeValueSet['name'] . '-PB-' . $key) {
                                $propertyNameMatched = true;
                                $propertyId = $propertyName->propertyId;
                            }
                        }
                    }

                    if(!$propertyNameMatched) {
                        $property = $propertyRepository->createProperty($attributeData);
                        $propertyId = $property->id;

                        try {
                            foreach($attributeData['names'] as $name) {
                                $name['propertyId'] = $propertyId;
                                $propertyName = $propertyNameRepository->createName($name);
                            }

                        } catch(\Exception $e) {

                        }
                    }

                    $propertyRelationRepository->createRelation([
                        'propertyId' => $propertyId,
                        'relationTargetId' => 1107,
                        'relationTypeIdentifier' => 'item',
                        'relationValues' => [
                            [
                                'lang' => 'de',
                                'value' => 'test-value',
                                'description' => 'test-description'
                            ]
                        ]
                    ]);
                }*/
            /*}*/

        $result = [
          'property' => $property->id,
          'propertySelection' => $propertySelection->id
        ];

        return $result;

    }


    private function propertyValues($values)
    {
        $propertyValuesSet = [];
        foreach($values as $key => $value)
        {
            $data['lang'] = 'de';
            $data['value'] = $value . '-PB-' . $key;

            array_push($propertyValuesSet, $data);
        }

        return $propertyValuesSet;
    }


    public function getPBAttributes()
    {
        /*$app = pluginApp(AppController::class);
        $attributeValueSet = $app->authenticate('pandaBlack_attributes', $categoryId);

        if(isset($attributeValueSet)) {
            return $attributeValueSet;
        }*/

        $createResult = $this->createPBAttributes();

        $propertyRepo = pluginApp(PropertyRepositoryContract::class);

        $paginatedResult = $propertyRepo->listProperties();

        //$propertiesList[] = $paginatedResult->getResult();

        return $createResult;
    }


    public function deletePBProperties()
    {
        $settingRepo = pluginApp(SettingsRepositoryContract::class);
        $settingRepo->deleteAll('PandaBlack', 'property');
    }
}