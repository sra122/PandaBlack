<?php

namespace PandaBlack\Repositories;

use PandaBlack\Contracts\AttributeValuesRepositoryContract;
use PandaBlack\Models\Attribute;
use PandaBlack\Models\AttributeValue;
use PandaBlack\Models\Category;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;

class AttributeValuesRepository implements AttributeValuesRepositoryContract
{
    /**
     * @var DataBase
     */
    private $database;

    /**
     * CategoriesRepository constructor.
     */
    public function __construct()
    {
        $this->database = pluginApp(DataBase::class);
    }

    /**
     * @param array $data
     * @return Category
     */
    public function createAttributeValue(array $data): AttributeValue
    {
        $attributeValue = pluginApp(AttributeValue::class);

        $attributeValueData = $this->database->query(AttributeValue::class)
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


    /**
     * @param $id
     * @return array
     */
    public function getAttributeValue($id): array
    {
        $attributeValueData = $this->database->query(AttributeValue::class)
            ->where('attribute_value_identifier', '=', $id)->get();

        return $attributeValueData;
    }


    /**
     * @param $id
     * @return array
     */
    public function getAttributeValuesForAttribute($id): array
    {
        $attributeValueData = $this->database->query(AttributeValue::class)
            ->where('attribute_identifier', '=', $id)->get();

        return $attributeValueData;
    }


    /**
     * @param $id
     * @return Attribute
     */
    public function getAttributeValueForCategory($id): array
    {
        $attributeValueData = $this->database->query(AttributeValue::class)
            ->where('category_identifier', '=', $id)->get();

        return $attributeValueData;
    }

    /**
     * @param $id
     * @return AttributeValue
     */
    public function getAttributeValueForAttributeId($id): array
    {
        $attributeValueData = $this->database->query(AttributeValue::class)
            ->where('attribute_value_identifier', '=', $id)->get();

        return $attributeValueData;
    }


    /**
     * @param $id
     * @param $attributeValueName
     * @return AttributeValue
     */
    public function updateAttributeValue($id, $attributeValueName): AttributeValue
    {
        $attributeValueData = $this->database->query(AttributeValue::class)
            ->where('attribute_value_identifier', '=', $id)->get();

        $attributeValue = $attributeValueData[0];
        $attributeValue->name = $attributeValueName;
        $this->database->save($attributeValue);

        return $attributeValue;
    }


    /**
     * @param $id
     * @return Attribute
     */
    public function deleteAttributeValue($id): AttributeValue
    {
        $attributeValueData = $this->database->query(AttributeValue::class)
            ->where('attribute_value_identifier', '=', $id)->get();

        $attributeValue = $attributeValueData[0];
        $this->database->delete($attributeValue);

        return $attributeValue;
    }
}