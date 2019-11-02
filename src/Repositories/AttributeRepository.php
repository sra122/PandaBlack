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

        $attributeData = $this->database->query(Attributes::class)
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
     * @return mixed
     */
    public function getAttributeForCategory($id)
    {
        $attributeData = $this->database->query(Attributes::class)->where('category_identifier', '=', $id)->get();

        return $attributeData;
    }


    /**
     * @param $id
     * @return mixed
     */
    public function getAttribute($id)
    {
        $attributeData = $this->database->query(Attributes::class)->where('attribute_identifier', '=', $id)->get();

        if(count($attributeData) > 0) {
            return $attributeData[0];
        } else {
            return false;
        }
    }


    /**
     * @param $id
     * @param $attributeName
     * @return mixed
     */
    public function updateAttribute($id, $attributeName)
    {
        $attributeData = $this->database->query(Attributes::class)->where('attribute_identifier', '=', $id)->get();

        if(count($attributeData) > 0) {
            $attribute = $attributeData[0];
            $attribute->name = $attributeName;
            $this->database->save($attribute);

            return $attribute;
        } else {
            return false;
        }
    }


    /**
     * @param $id
     * @return Attributes
     */
    public function deleteAttribute($id): Attributes
    {
        $attributeData = $this->database->query(Attributes::class)->where('attribute_identifier', '=', $id)->get();

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


    /**
     * @return array
     */
    public function getUniqueCategories(): array
    {
        $categoriesInfo = [];
        $categories = $this->database->query(Attributes::class)->where('id' , '!=', null)->get();

        foreach($categories as $category)
        {
            if(!isset($categoriesInfo[$category->category_identifier])) {
                $categoriesInfo[$category->category_identifier] = $category->category_identifier;
            }
        }

        return $categoriesInfo;
    }
}