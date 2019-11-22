<?php

namespace PandaBlack\Contracts;

use PandaBlack\Models\Attribute;

interface AttributesRepositoryContract
{

    /**
     * @param array $data
     * @return Attribute
     */
    public function createAttribute(array $data): Attribute;


    /**
     * @param $id
     * @return array
     */
    public function getAttributeForCategory($id): array;

    /**
     * @param $id
     * @return array
     */
    public function getAttribute($id): array;

    /**
     * @param $id
     * @param $attributeName
     * @return Attribute
     */
    public function updateAttribute($id, $attributeName): Attribute;

    /**
     * @param $id
     * @return Attribute
     */
    public function deleteAttribute($id): Attribute;

    /**
     * @param $categoryId
     * @return mixed
     */
    public function deleteAttributeForCategory($categoryId);

    /**
     * @return array
     */
    public function getUniqueCategories(): array;
}