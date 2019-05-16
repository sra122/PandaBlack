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
        $propertyNameRepository = pluginApp(PropertyNameRepositoryContract::class);

        $attributeData = [
            'cast' => 'selection',
            'typeIdentifier' => 'item',
            'position' => 0,
            'names' => [
                [
                    'lang' => 'de',
                    'name' => 'Farbe2'
                ]
            ]
        ];

        $property = $propertyRepository->createProperty($attributeData);


        foreach($attributeData['names'] as $attributeName)
        {
            $attributeName['propertyId'] = $property->id;
            $propertyName = $propertyNameRepository->createName($attributeName);
        }

        $dropdownValues = ['Orange', 'Black', 'Green'];

        foreach($dropdownValues as $dropdownValue)
        {
            $selectionData = [
                'propertyId' => $property->id,
                'relation' => [
                    [
                        'relationValues' => [
                            [
                                'value' => $dropdownValue,
                                'lang' => 'de',
                                'description' => $dropdownValue . ' Description'
                            ]
                        ]
                    ]
                ]
            ];

            $propertySelectionRepo = pluginApp(PropertySelectionRepositoryContract::class);
            $propertySelection = $propertySelectionRepo->createPropertySelection($selectionData);

        }

        /*$dropdownValue = [
            'propertyId' => $property->id,
            'relation' => [
                [
                    'relationValues' => [
                        [
                            'value' => 'Orange',
                            'lang' =>'de',
                            'description' => 'Orange Description'
                        ]
                    ]
                ],
                [
                    'relationValues' => [
                        [
                            'value' => 'Black',
                            'lang' => 'de',
                            'description' => 'Black Description'
                        ]
                    ]
                ],
                [
                    'relationValues' => [
                        [
                            'value' => 'Green',
                            'lang' => 'de',
                            'description' => 'Green Description'
                        ]
                    ]
                ]
            ]
        ];*/



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
                }
            }*/

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

        $propertyList = $propertyRepo->listProperties(1, 50);

        //$propertiesList[] = $paginatedResult->getResult();

        $result = [
            'creation' => $createResult,
            'propertyList' => $propertyList
        ];

        return $result;
    }


    public function deletePBProperties()
    {
        $settingRepo = pluginApp(SettingsRepositoryContract::class);
        $settingRepo->deleteAll('PandaBlack', 'property');
    }
}