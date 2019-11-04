<?php

namespace PandaBlack\Repositories;

use PandaBlack\Contracts\PropertiesRepositoryContract;
use PandaBlack\Models\Attributes;
use PandaBlack\Models\Properties;
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
     * CategoryRepository constructor.
     */
    public function __construct()
    {
        $this->database = pluginApp(DataBase::class);
    }

    /**
     * @param array $data
     * @return Properties
     */
    public function createProperty(array $data): Properties
    {
        $property = pluginApp(Properties::class);

        $propertyData = $this->database->query(Properties::class)
            ->where('type', '=', $data['type'])
            ->where('value', '=', $data['value'])->get();

        if(count($propertyData) <= 0 || $propertyData === null) {
            $property->type = $data['type'];
            $property->value = $data['value'];
            $this->database->save($property);

            return $property;
        } else {
            return $propertyData[0];
        }
    }


    public function getProperty($type, $value): array
    {
        $propertyData = $this->database->query(Properties::class)
            ->where('type', '=', $type)
            ->where('value', '=', $value)
            ->get();

        return $propertyData;
    }



    public function getProperties(): array
    {
        $propertyData = [];
        $propertyValueData = [];

        $properties = $this->database->query(Properties::class)->where('value' ,'!=', 'null')->get();

        foreach($properties as $property)
        {
            if($property['type'] === self::PROPERTY) {
                $propertyData[self::PROPERTY] = $property['value'];
            } else {
                $propertyValueData[self::PROPERTY_VALUE] = $property['value'];
            }
        }

        $propertiesData = [
            self::PROPERTY => $propertyData,
            self::PROPERTY_VALUE => $propertyValueData
        ];

        return $propertiesData;
    }
}