<?php

namespace PandaBlack\Contracts;

use PandaBlack\Models\Property;

interface PropertiesRepositoryContract
{
    /**
     * @param array $data
     * @return Property
     */
    public function createProperty(array $data): Property;

    /**
     * @param $type
     * @param $value
     * @param $key
     * @return array
     */
    public function getProperty($type, $value, $key): array;

    /**
     * @return mixed
     */
    public function getProperties();


    /**
     * @param $id
     * @return array
     */
    public function getSingleProperty($id): array ;
}