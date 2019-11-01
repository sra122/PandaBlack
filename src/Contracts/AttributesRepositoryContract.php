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
     * @return mixed
     */
    public function getAttributeForCategory($id);


    /**
     * @param $id
     * @return mixed
     */
    public function getAttribute($id);

    /**
     * @param $id
     * @param $attributeName
     * @return mixed
     */
    public function updateAttribute($id, $attributeName);

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