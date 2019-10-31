<?php

namespace PandaBlack\Repositories;

use PandaBlack\Contracts\AttributesRepositoryContract;
use PandaBlack\Contracts\AttributeValuesRepositoryContract;
use PandaBlack\Contracts\CategoriesRepositoryContract;
use PandaBlack\Models\Attributes;
use PandaBlack\Models\AttributeValues;
use PandaBlack\Models\Categories;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use Plenty\Modules\Plugin\DataBase\Contracts\Query;

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

        $attributeValueData = $this->database->query(AttributeValues::class)->where('name', '=', $data['attributeValueName'])->where('attribute_identifier', '=', $data['attributeId'])->get();

        if(count($attributeValueData) <= 0 || $attributeValueData === null) {
            $attributeValue->category_identifier = $data['categoryId'];
            $attributeValue->attribute_identifier = $data['attributeId'];
            $attributeValue->name = $data['attributeName'];

            $this->database->save($attributeValue);

            return $attributeValue;
        }
    }


    /**
     * @param $id
     * @return array
     */
    public function getAttributeValuesForAttribute($id): array
    {
        $attributeValueData = $this->database->query(AttributeValues::class)->where('attribute_identifier', '=', $id)->get();

        return $attributeValueData;
    }


    /**
     * @param $id
     * @return Attributes
     */
    public function getAttributeValueForCategory($id): array
    {
        $attributeValueData = $this->database->query(AttributeValues::class)->where('category_identifier', '=', $id)->get();

        return $attributeValueData;
    }

    /**
     * @param $id
     * @return AttributeValues
     */
    public function getAttributeValue($id): AttributeValues
    {
        $attributeValueData = $this->database->query(AttributeValues::class)->where('id', '=', $id)->get();

        return $attributeValueData[0];
    }


    /**
     * @param $id
     * @param $attributeValueName
     * @return AttributeValues
     */
    public function updateAttributeValue($id, $attributeValueName): AttributeValues
    {
        $attributeValueData = $this->database->query(AttributeValues::class)->where('id', '=', $id)->get();

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
        $attributeValueData = $this->database->query(AttributeValues::class)->where('id', '=', $id)->get();

        $attributeValue = $attributeValueData[0];
        $this->database->delete($attributeValue);

        return $attributeValue;
    }
}