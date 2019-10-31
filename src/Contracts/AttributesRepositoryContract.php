<?php

namespace PandaBlack\Contracts;

use PandaBlack\Models\Attributes;

interface AttributesRepositoryContract
{

    public function createAttribute(array $data): Attributes;


    /**
     * @param $id
     * @return array
     */
    public function getAttributeForCategory($id): array;


    /**
     * @param $id
     * @param $categoryTreePath
     * @return Attributes
     */
    public function getAttribute($id): Attributes;


    /**
     * @param $id
     * @return Attributes
     */
    public function deleteAttribute($id): Attributes;


    /**
     * @param $id
     * @param $attributeName
     * @return Attributes
     */
    public function updateAttribute($id, $attributeName): Attributes;
}