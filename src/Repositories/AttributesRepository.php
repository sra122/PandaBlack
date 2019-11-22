<?php

namespace PandaBlack\Repositories;

use PandaBlack\Contracts\AttributesRepositoryContract;
use PandaBlack\Models\Attribute;
use PandaBlack\Models\AttributeValue;
use PandaBlack\Models\Category;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;

class AttributesRepository implements AttributesRepositoryContract
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
     * @return Attribute
     */
    public function createAttribute(array $data): Attribute
    {
        $attribute = pluginApp(Attribute::class);

        $attributeData = $this->database->query(Attribute::class)
            ->where('name', '=', $data['attributeName'])
            ->where('category_identifier', '=', $data['categoryId'])->get();

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
        $attributeData = $this->database->query(Attribute::class)->where('category_identifier', '=', $id)->get();

        return $attributeData;
    }


    /**
     * @param $id
     * @return array
     */
    public function getAttribute($id): array
    {
        $attributeData = $this->database->query(Attribute::class)->where('attribute_identifier', '=', $id)->get();

        return $attributeData;
    }


    /**
     * @param $id
     * @param $attributeName
     * @return Attribute
     */
    public function updateAttribute($id, $attributeName): Attribute
    {
        $attributeData = $this->database->query(Attribute::class)->where('attribute_identifier', '=', $id)->get();

        $attribute = $attributeData[0];
        $attribute->name = $attributeName;
        $this->database->save($attribute);

        return $attribute;
    }


    /**
     * @param $id
     * @return Attribute
     */
    public function deleteAttribute($id): Attribute
    {
        $attributeData = $this->database->query(Attribute::class)->where('attribute_identifier', '=', $id)->get();

        $attribute = $attributeData[0];
        $this->database->delete($attribute);

        //Attribute Values
        $attributeValues = $this->database->query(AttributeValue::class)
            ->where('attribute_identifier', '=', $id)->get();

        foreach($attributeValues as $attributeValue)
        {
            $this->database->delete($attributeValue);
        }

        return $attribute;
    }


    /**
     * @param $id
     * @return mixed|void
     */
    public function deleteAttributeForCategory($id)
    {
        $attributes = $this->database->query(Attribute::class)->where('category_identifier', '=', $id)->get();

        foreach($attributes as $attribute)
        {
            $this->database->delete($attribute);
        }
    }


    /**
     * @return array
     */
    public function getUniqueCategories(): array
    {
        $categoriesInfo = [];
        $categories = $this->database->query(Attribute::class)->where('id' , '!=', null)->get();

        foreach($categories as $category)
        {
            if(!isset($categoriesInfo[$category->category_identifier])) {
                $categoriesInfo[$category->category_identifier] = $category->category_identifier;
            }
        }

        return $categoriesInfo;
    }
}