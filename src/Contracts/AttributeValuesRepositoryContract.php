<?php

namespace PandaBlack\Contracts;

use PandaBlack\Models\Attribute;
use PandaBlack\Models\AttributeValue;

interface AttributeValuesRepositoryContract
{

    /**
     * @param array $data
     * @return Attribute
     */
    public function createAttributeValue(array $data): AttributeValue;

    /**
     * @param $id
     * @return array
     */
    public function getAttributeValue($id): array;

    /**
     * @param $id
     * @return array
     */
    public function getAttributeValuesForAttribute($id): array;

    /**
     * @param $id
     * @return array
     */
    public function getAttributeValueForAttributeId($id): array;

    /**
     * @param $id
     * @return array
     */
    public function getAttributeValueForCategory($id): array;

    /**
     * @param $id
     * @param $attributeName
     * @return Attribute
     */
    public function updateAttributeValue($id, $attributeName): AttributeValue;

    /**
     * @param $id
     * @return Attribute
     */
    public function deleteAttributeValue($id): AttributeValue;

    /**
     * @param $attributeId
     * @return mixed
     */
    public function deleteAttributeValueForAttribute($attributeId);

    /**
     * @param $categoryId
     * @return mixed
     */
    public function deleteAttributeValueForCategory($categoryId);

}