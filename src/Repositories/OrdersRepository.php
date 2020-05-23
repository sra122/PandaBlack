<?php

namespace PandaBlack\Repositories;

use PandaBlack\Contracts\OrdersRepositoryContract;
use PandaBlack\Models\Order;;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;

class OrdersRepository implements OrdersRepositoryContract
{
    /**
     * @var DataBase
     */
    private $database;

    /**
     * CategoriesRepository constructor.
     */
    public function __construct()
    {
        $this->database = pluginApp(DataBase::class);
    }

    /**
     * @param array $data
     * @return Order
     */
    public function createOrder(array $data): Order
    {
        $order = pluginApp(Order::class);

        $orderData = $this->database->query(Order::class)
            ->where('reference_key', '=', $data['referenceKey'])
            ->where('order_id', '=', $data['order_id'])
            ->get();

        if(count($orderData) <= 0 || $orderData === null) {
            $order->reference_key = $data['referenceKey'];
            $order->order_id = $data['order_id'];
            $this->database->save($order);

            return $order;
        } else {
            return $orderData[0];
        }
    }


    /**
     * @return array
     */
    public function getReferenceKeys(): array
    {
        $referenceKeys = [];

        $orders = $this->database->query(Order::class)->get();

        foreach($orders as $order)
        {
            $referenceKeys[$order->reference_key] = $order->order_id;
        }

        return $referenceKeys;
    }

    /**
     * @param $referenceKey
     * @return mixed|null
     */
    public function getOrderInfoWithReferenceKey($referenceKey)
    {
        $order = $this->database->query(Order::class)
            ->where('reference_key', '=', $referenceKey)->get();

        if(count($order) <= 0) {
            return null;
        } else {
            return $order[0];
        }
    }
}