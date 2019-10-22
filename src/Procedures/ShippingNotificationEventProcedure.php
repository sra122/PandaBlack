<?php

namespace PandaBlack\Procedures;

use PandaBlack\Controllers\AppController;
use PandaBlack\Helpers\SettingsHelper;
use Plenty\Modules\EventProcedures\Events\EventProceduresTriggered;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Models\Legacy\Order;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;
use Plenty\Modules\Order\Shipping\Contracts\ParcelServicePresetRepositoryContract;

use Etsy\Api\Services\ReceiptService;
use Etsy\Helper\ShippingHelper;
use Plenty\Modules\Order\Shipping\ParcelService\Models\ParcelServicePreset;
use Plenty\Plugin\Log\Loggable;

/**
 * Class ShippingNotificationEventProcedure
 */
class ShippingNotificationEventProcedure
{
	use Loggable;

	/**
	 * @var SettingsHelper
	 */
	private $settingsHelper;

	/**
	 * @param ReceiptService $receiptService
	 * @param ShippingHelper $shippingHelper
	 * @param SettingsHelper $settingsHelper
	 */
	public function __construct(SettingsHelper $settingsHelper, ShippingHelper $shippingHelper)
	{
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
		$referenceId = $this->getReferenceId($order);

		if($referenceId !== null)
        {
            $app = pluginApp(AppController::class);
            $shippingInfo = [
                'trackingCode' => $trackingCode,
                'carrierName' => $carrierName,
                'referenceId' => $this->getReferenceId($order)
            ];
            $app->authenticate('pandaBlack_product_errors', null, null, $shippingInfo);
        }

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
        /** @var OrderRepositoryContract $orderRepo */
        $orderRepo = pluginApp(OrderRepositoryContract::class);

        $packageNumbers = $orderRepo->getPackageNumbers($order->id);

        if(is_array($packageNumbers) && count($packageNumbers))
        {
            return $packageNumbers[0];
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
        /** @var ParcelServicePresetRepositoryContract $parcelServicePresetRepo */
        $parcelServicePresetRepo = pluginApp(ParcelServicePresetRepositoryContract::class);

        $parcelServicePreset = $parcelServicePresetRepo->getPresetById($order->shippingProfileId);

        if($parcelServicePreset instanceof ParcelServicePreset)
        {
            return $parcelServicePreset->parcelService->parcel_service_type;
        }

		return null;
	}


    /**
     * @param $order
     * @return |null
     */
	private function getReferenceId($order)
    {
        $existingOrders = $this->settingsHelper->get(SettingsHelper::ORDERS);
        if($existingOrders === null) {
            $this->settingsHelper->set(SettingsHelper::ORDERS, []);
        } else if (!is_null($existingOrders)) {
            foreach($existingOrders as $existingOrder)
            {
                if($existingOrder['plentyOrderId'] === $order->id) {
                    return $existingOrder['referenceId'];
                }
            }
        }

        return null;
    }
}
