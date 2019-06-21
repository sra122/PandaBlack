<?php

namespace PandaBlack\Procedures;

use Plenty\Modules\EventProcedures\Events\EventProceduresTriggered;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;

class ShippingNotificationEventProcedure
{
    public function run(EventProceduresTriggered $eventTriggered)
    {
        $order = $eventTriggered->getOrder();

        $trackingCode = $this->getTrackingCode($order);

    }


    private function getTrackingCode($order)
    {
        $orderRepo = pluginApp(OrderRepositoryContract::class);

        $packageNumbers = $orderRepo->getPackageNumbers($order->id);

        if(is_array($packageNumbers) && count($packageNumbers)) {
            return $packageNumbers[0];
        }
    }
}