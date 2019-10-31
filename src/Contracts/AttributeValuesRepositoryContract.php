<?php

namespace PandaBlack\Contracts;

use PandaBlack\Models\Attributes;
use PandaBlack\Models\AttributeValues;

interface AttributeValuesRepositoryContract
{

    /**
     * @param array $data
     * @return Attributes
     */
    public function createAttributeValue(array $data): AttributeValues;

    /**
     * @param $id
     * @return array
     */
    public function getAttributeValuesForAttribute($id): array;

    /**
     * @param $id
     * @param $categoryTreePath
     * @return Attributes
     */
    public function getAttributeValue($id): AttributeValues;

    /**
     * @param $id
     * @return array
     */
    public function getAttributeValueForCategory($id): array;

    /**
     * @param $id
     * @return Attributes
     */
    public function deleteAttributeValue($id): AttributeValues;

    /**
     * @param $id
     * @param $attributeName
     * @return Attributes
     */
    public function updateAttributeValue($id, $attributeName): AttributeValues;
}