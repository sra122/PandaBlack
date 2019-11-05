<?php

namespace PandaBlack\Contracts;

use PandaBlack\Models\Property;

interface OrdersRepositoryContract
{
    /**
     * @param array $data
     * @return Property
     */
    public function createOrder(array $data): Property;

    /**
     * @return array
     */
    public function getReferenceKeys(): array;
}