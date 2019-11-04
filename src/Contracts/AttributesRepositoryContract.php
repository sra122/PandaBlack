<?php

namespace PandaBlack\Contracts;

use PandaBlack\Models\Attributes;

interface AttributesRepositoryContract
{

    /**
     * @param array $data
     * @return Attributes
     */
    public function createAttribute(array $data): Attributes;


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
     * @return Attributes
     */
    public function updateAttribute($id, $attributeName): Attributes;

    /**
     * @param $id
     * @return Attributes
     */
    public function deleteAttribute($id): Attributes;

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