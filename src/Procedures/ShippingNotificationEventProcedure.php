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
        try
        {
            $orderRepo = pluginApp(OrderRepositoryContract::class);

            $packageNumbers = $orderRepo->getPackageNumbers($order->id);

            if(is_array($packageNumbers) && count($packageNumbers)) {
                return $packageNumbers[0];
            }
        }
        catch (\Exception $ex)
        {
            $this->getLogger(__FUNCTION__)
                ->setReferenceType('orderId')
                ->setReferenceValue($order->id)
                ->error('PandaBlack::order.trackingCodeError', $ex->getMessage());
        }

        return null;
    }
}