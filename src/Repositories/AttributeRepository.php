<?php

namespace PandaBlack\Repositories;

use PandaBlack\Contracts\AttributesRepositoryContract;
use PandaBlack\Models\Attributes;
use PandaBlack\Models\Categories;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;

class AttributeRepository implements AttributesRepositoryContract
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
    public function createAttribute(array $data): Attributes
    {
        $attribute = pluginApp(Attributes::class);

        $attributeData = $this->database->query(Attributes::class)->where('name', '=', $data['attributeName'])->where('category_identifier', '=', $data['categoryId'])->get();

        if(count($attributeData) <= 0 || $attributeData === null) {
            $attribute->category_identifier = $data['categoryId'];
            $attribute->name = $data['attributeName'];
            $attribute->attribute_identifier = $data['attributeId'];
            $this->database->save($attribute);

            return $attribute;
        } else {
            return $attributeData[0];
        }
    }


    /**
     * @param $id
     * @return array
     */
    public function getAttributeForCategory($id): array
    {
        $attributeData = $this->database->query(Attributes::class)->where('category_identifier', '=', $id)->get();

        return $attributeData;
    }


    /**
     * @param $id
     * @return Attributes
     */
    public function getAttribute($id): Attributes
    {
        $attributeData = $this->database->query(Attributes::class)->where('id', '=', $id)->get();

        return $attributeData[0];
    }


    /**
     * @param $id
     * @param $attributeName
     * @return Attributes
     */
    public function updateAttribute($id, $attributeName): Attributes
    {
        $attributeData = $this->database->query(Attributes::class)->where('id', '=', $id)->get();

        $attribute = $attributeData[0];
        $attribute->name = $attributeName;
        $this->database->save($attribute);

        return $attribute;
    }


    /**
     * @param $id
     * @return Attributes
     */
    public function deleteAttribute($id): Attributes
    {
        $attributeData = $this->database->query(Attributes::class)->where('id', '=', $id)->get();

        $attribute = $attributeData[0];
        $this->database->delete($attribute);

        return $attribute;
    }


    /**
     * @param $id
     * @return mixed|void
     */
    public function deleteAttributeForCategory($id)
    {
        $attributes = $this->database->query(Attributes::class)->where('category_identifier', '=', $id)->get();

        foreach($attributes as $attribute)
        {
            $this->database->delete($attribute);
        }
    }
}