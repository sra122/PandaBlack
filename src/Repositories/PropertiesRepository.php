<?php

namespace PandaBlack\Repositories;

use PandaBlack\Contracts\PropertiesRepositoryContract;
use PandaBlack\Models\Property;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;

class PropertiesRepository implements PropertiesRepositoryContract
{
    /**
     * @var DataBase
     */
    private $database;
    const PROPERTY = 'property';
    const PROPERTY_VALUE = 'propertyValue';

    /**
     * CategoriesRepository constructor.
     */
    public function __construct()
    {
        $this->database = pluginApp(DataBase::class);
    }

    /**
     * @param array $data
     * @return Property
     */
    public function createProperty(array $data): Property
    {
        $property = pluginApp(Property::class);

        $propertyData = $this->database->query(Property::class)
            ->where('type', '=', $data['type'])
            ->where('value', '=', $data['value'])
            ->where('key', '=', $data['key'])
            ->get();

        if(count($propertyData) <= 0 || $propertyData === null) {
            $property->type = $data['type'];
            $property->value = $data['value'];
            $property->key = $data['key'];
            $this->database->save($property);

            return $property;
        } else {
            return $propertyData[0];
        }
    }


    /**
     * @param $type
     * @param $value
     * @param $key
     * @return array
     */
    public function getProperty($type, $value, $key): array
    {
        $propertyData = $this->database->query(Property::class)
            ->where('type', '=', $type)
            ->where('value', '=', $value)
            ->where('key', '=', $key)
            ->get();

        return $propertyData;
    }


    /**
     * @return false|mixed|string
     */
    public function getProperties()
    {
        $propertyData = [];
        $propertyValueData = [];

        $properties = $this->database->query(Property::class)->get();

        foreach($properties as $property)
        {
            if($property->type === self::PROPERTY) {
                $propertyData[$property->key] = $property->value;
            } else {
                $propertyValueData[$property->key] = $property->value;
            }
        }

        $propertiesData = [
            'property' => $propertyData,
            'propertyValue' => $propertyValueData
        ];

        return json_encode($propertiesData, true);
    }


    /**
     * @param $id
     * @return array
     */
    public function getSingleProperty($id): array
    {
        $property = $this->database->query(Property::class)->get();

        return $property;
    }
}