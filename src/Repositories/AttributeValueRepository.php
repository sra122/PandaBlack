<?php

namespace PandaBlack\Repositories;

use PandaBlack\Contracts\AttributeValuesRepositoryContract;
use PandaBlack\Models\Attributes;
use PandaBlack\Models\AttributeValues;
use PandaBlack\Models\Categories;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;

class AttributeValueRepository implements AttributeValuesRepositoryContract
{
    /**
     * @var DataBase
     */
    private $database;

    /**
     * CategoryRepository constructor.
     */
    public function __construct()
    {
        $this->database = pluginApp(DataBase::class);
    }

    /**
     * @param array $data
     * @return Categories
     */
    public function createAttributeValue(array $data): AttributeValues
    {
        $attributeValue = pluginApp(AttributeValues::class);

        $attributeValueData = $this->database->query(AttributeValues::class)
            ->where('name', '=', $data['attributeValueName'])
            ->where('attribute_identifier', '=', $data['attributeId'])
            ->where('attribute_value_identifier', '=', $data['attributeValueId'])
            ->get();

        if(count($attributeValueData) <= 0 || $attributeValueData === null) {
            $attributeValue->category_identifier = $data['categoryId'];
            $attributeValue->attribute_identifier = $data['attributeId'];
            $attributeValue->name = $data['attributeValueName'];
            $attributeValue->attribute_value_identifier = $data['attributeValueId'];

            $this->database->save($attributeValue);

            return $attributeValue;
        } else {
            return $attributeValueData[0];
        }
    }



    public function getAttributeValue($id)
    {
        $attributeValueData = $this->database->query(AttributeValues::class)
            ->where('attribute_value_identifier', '=', $id)->get();

        if(count($attributeValueData) > 0) {
            return $attributeValueData[0];
        } else {
            return false;
        }
    }


    /**
     * @param $id
     * @return array
     */
    public function getAttributeValuesForAttribute($id): array
    {
        $attributeValueData = $this->database->query(AttributeValues::class)
            ->where('attribute_identifier', '=', $id)->get();

        return $attributeValueData;
    }


    /**
     * @param $id
     * @return Attributes
     */
    public function getAttributeValueForCategory($id): array
    {
        $attributeValueData = $this->database->query(AttributeValues::class)
            ->where('category_identifier', '=', $id)->get();

        return $attributeValueData;
    }

    /**
     * @param $id
     * @return AttributeValues
     */
    public function getAttributeValueForAttributeId($id): AttributeValues
    {
        $attributeValueData = $this->database->query(AttributeValues::class)
            ->where('attribute_value_identifier', '=', $id)->get();

        return $attributeValueData[0];
    }


    /**
     * @param $id
     * @param $attributeValueName
     * @return AttributeValues
     */
    public function updateAttributeValue($id, $attributeValueName): AttributeValues
    {
        $attributeValueData = $this->database->query(AttributeValues::class)
            ->where('attribute_value_identifier', '=', $id)->get();

        $attributeValue = $attributeValueData[0];
        $attributeValue->name = $attributeValueName;
        $this->database->save($attributeValue);

        return $attributeValue;
    }


    /**
     * @param $id
     * @return Attributes
     */
    public function deleteAttributeValue($id): AttributeValues
    {
        $attributeValueData = $this->database->query(AttributeValues::class)
            ->where('attribute_value_identifier', '=', $id)->get();

        $attributeValue = $attributeValueData[0];
        $this->database->delete($attributeValue);

        return $attributeValue;
    }


    /**
     * @param $attributeId
     * @return mixed|void
     */
    public function deleteAttributeValueForAttribute($attributeId)
    {
        $attributeValues = $this->database->query(AttributeValues::class)
            ->where('attribute_identifier', '=', $attributeId)->get();

        foreach($attributeValues as $attributeValue)
        {
            $this->database->delete($attributeValue);
        }
    }

    /**
     * @param $categoryId
     * @return mixed|void
     */
    public function deleteAttributeValueForCategory($categoryId)
    {
        $attributeValues = $this->database->query(AttributeValues::class)
            ->where('category_identifier', '=', $categoryId)->get();

        foreach($attributeValues as $attributeValue)
        {
            $this->database->delete($attributeValue);
        }
    }
}