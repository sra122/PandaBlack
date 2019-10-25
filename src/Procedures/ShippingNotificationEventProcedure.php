<?php

namespace PandaBlack\Procedures;

use PandaBlack\Controllers\AppController;
use PandaBlack\Helpers\SettingsHelper;
use Plenty\Modules\EventProcedures\Events\EventProceduresTriggered;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Models\Legacy\Order;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;
use Plenty\Modules\Order\Shipping\Contracts\ParcelServicePresetRepositoryContract;
use Plenty\Modules\Order\Shipping\ParcelService\Models\ParcelServicePreset;
use Plenty\Plugin\Log\Loggable;

/**
 * Class ShippingNotificationEventProcedure
 */
class ShippingNotificationEventProcedure
{
    use Loggable;

    /**
     * @var AppController
     */
    private $api;

    /**
     * @var SettingsHelper
     */
    private $settingsHelper;

    /**
     * @param AppController $appController
     * @param SettingsHelper $settingsHelper
     */
    public function __construct(AppController $appController, SettingsHelper $settingsHelper)
    {
        $this->api = $appController;
        $this->settingsHelper = $settingsHelper;
    }

    /**
     * Mark an receipt as shipped on Etsy.
     *
     * @param EventProceduresTriggered $eventTriggered
     */
    public function run(EventProceduresTriggered $eventTriggered)
    {
        /** @var Order $order */
        $order = $eventTriggered->getOrder();

        $trackingCode = $this->getTrackingCode($order);
        $carrierName  = $this->getCarrierName($order);
        $referenceNumber = $this->getReferenceId($order);

        if(strlen($carrierName) && strlen($trackingCode) && $referenceNumber) {
            $this->api->shippingNotification($trackingCode, $carrierName, $referenceNumber);
        }
    }

    private function getReferenceId($order)
    {
        $ordersInfo = array_reverse($this->settingsHelper->get(SettingsHelper::ORDER_DATA));

        return isset($ordersInfo[$order->id]) ? $ordersInfo[$order->id] : false;
    }

    /**
     * Get tracking code.
     *
     * @param Order $order
     *
     * @return mixed|null
     */
    private function getTrackingCode($order)
    {
        try
        {
            /** @var OrderRepositoryContract $orderRepo */
            $orderRepo = pluginApp(OrderRepositoryContract::class);

            $packageNumbers = $orderRepo->getPackageNumbers($order->id);

            if(is_array($packageNumbers) && count($packageNumbers))
            {
                return $packageNumbers[0];
            }
        }
        catch(\Exception $ex)
        {
            $this->getLogger(__FUNCTION__)
                ->setReferenceType('orderId')
                ->setReferenceValue($order->id)
                ->error('Tracking Code is not determined.', $ex->getMessage());
        }

        return null;
    }

    /**
     * Get the carrier name base on the order shipping profile.
     *
     * @param Order $order
     *
     * @return mixed|null
     */
    private function getCarrierName($order)
    {
        try
        {
            /** @var ParcelServicePresetRepositoryContract $parcelServicePresetRepo */
            $parcelServicePresetRepo = pluginApp(ParcelServicePresetRepositoryContract::class);

            $parcelServicePreset = $parcelServicePresetRepo->getPresetById($order->shippingProfileId);

            if($parcelServicePreset instanceof ParcelServicePreset)
            {
                return $parcelServicePreset->backendName;
            }
        }
        catch(\Exception $ex)
        {
            $this->getLogger(__FUNCTION__)
                ->setReferenceType('orderId')
                ->setReferenceValue($order->id)
                ->error('Carrier data is not determined.', $ex->getMessage());
        }

        return null;
    }
}
