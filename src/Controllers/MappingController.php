<?php
/**
 * Created by PhpStorm.
 * User: sravan
 * Date: 13.06.19
 * Time: 12:07
 */

namespace PandaBlack\Controllers;

use PandaBlack\Helpers\SettingsHelper;
use PandaBlack\Repositories\PropertiesRepository;
use Plenty\Modules\Property\Contracts\PropertyNameRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertyRelationRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertyRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertySelectionRepositoryContract;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;

class MappingController extends Controller
{
    public $mappingInfo = [];
    protected $settingsHelper;

    const PROPERTY = 'property';
    const PROPERTY_VALUE = 'propertyValue';

    public function __construct(SettingsHelper $settingsHelper)
    {
        $this->settingsHelper = $settingsHelper;
    }

    public function mapping(Request $request)
    {
        $mappingInfos = $request->get('mappingInformation');
        $categoryId = $request->get('categoryId');

        $this->mapProperties($mappingInfos);

        $this->mapPropertyValues($mappingInfos, $categoryId);

        $this->saveMapping();
    }

    /**
     * @param $mappingInfos
     */
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

    /**
     * @param $mappingInfos
     * @param $categoryId
     */
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
            }
        }
    }

    /**
     *
     */
    private function saveMapping()
    {
        $propertyRepo = pluginApp(PropertiesRepository::class);

        // Property
        foreach($this->mappingInfo['property'] as $key => $property)
        {
            $propertyData = [
                'type' => self::PROPERTY,
                'value' => $property,
                'key' => $key
            ];

            $propertyRepo->createProperty($propertyData);
        }

        // PropertyValue
        foreach($this->mappingInfo['propertyValue'] as $key => $propertyValue)
        {
            $propertyValueData = [
                'type' => self::PROPERTY_VALUE,
                'value' => $propertyValue,
                'key' => $key
            ];

            $propertyRepo->createProperty($propertyValueData);
        }
    }


    /**
     * @param $propertyName
     * @return \Plenty\Modules\Property\Models\Property
     */
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
            $propertyNameRepository->createName($propertyName);
        }

        return $property;
    }

    /**
     * @param $propertyName
     * @return bool
     */
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


    /**
     * @param $propertyId
     * @param $propertyValue
     * @return bool
     */
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


    /**
     * @return mixed
     */
    public function getProperties()
    {
        $propertyRepo = pluginApp(PropertiesRepository::class);
        return $propertyRepo->getProperties();
    }
}