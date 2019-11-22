<?php

namespace PandaBlack\Contracts;

use PandaBlack\Models\Order;

interface OrdersRepositoryContract
{
    /**
     * @param array $data
     * @return Order
     */
    public function createOrder(array $data): Order;

    /**
     * @return array
     */
    public function getReferenceKeys(): array;
}