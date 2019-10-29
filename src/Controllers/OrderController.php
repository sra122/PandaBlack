<?php

namespace PandaBlack\Controllers;

use PandaBlack\Helpers\SettingsHelper;
use Plenty\Plugin\Controller;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Plugin\Application;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
class OrderController extends Controller
{
    /** @var OrderRepositoryContract */
    protected $OrderRepository;
    /** @var AddressRepositoryContract */
    protected $AddressRepository;

    const BILLING_ADDRESS = 1;
    const DELIVERY_ADDRESS = 2;

    /** @var SettingsHelper */
    protected $Settings;
    protected $plentyId;

    public function __construct(SettingsHelper $SettingsHelper)
    {
        $this->Settings = $SettingsHelper;
        $this->plentyId = $this->getPlentyPluginInfo();
    }

    public function createOrder()
    {
        $app = pluginApp(AppController::class);
        $orders = $app->authenticate('pandaBlack_orders');

        if(!empty($orders)) {
            $ordersInfo = [];
            $this->OrderRepository = pluginApp(OrderRepositoryContract::class);
            $this->AddressRepository = pluginApp(AddressRepositoryContract::class);
            $settingsHelper = pluginApp(SettingsHelper::class);
            $ordersData = $settingsHelper->get(SettingsHelper::ORDER_DATA);
            if($ordersData === null || !(is_array($ordersData))) {
                $settingsHelper->set(SettingsHelper::ORDER_DATA, $ordersInfo);
            } else {
                $ordersInfo = $ordersData;
            }

            if(is_array($ordersInfo))
            {
                if(count($ordersInfo) <= 0) {
                    foreach($orders as $order)
                    {
                        $this->saveOrder($order);
                    }
                } else {
                    foreach($orders as $order)
                    {
                        if(!isset($ordersInfo[$order->reference_key])) {
                            $this->saveOrder($order);
                        }
                    }
                }
            }
        }

        return $orders;
    }


    public function test()
    {
        $settingsHelper = pluginApp(SettingsHelper::class);
        return $settingsHelper->getSettingProperty();
    }

    /**
     * @param $referenceKey
     * @param $orderDeliveryAddress
     * @return mixed
     */
    private function createDeliveryAddress($referenceKey, $orderDeliveryAddress)
    {
        $deliveryAddress = [
            'gender' => $orderDeliveryAddress->gender,
            'name1' => $orderDeliveryAddress->name,
            'address1' => $orderDeliveryAddress->address,
            'address2' => 'Ref Id ' . $referenceKey,
            'postalCode' => $orderDeliveryAddress->postal_code,
            'town' => $orderDeliveryAddress->city,
            'countryId' => $orderDeliveryAddress->country_id
        ];
        return $this->AddressRepository->createAddress($deliveryAddress)->id;
    }


    /**
     * @param $orderBillingAddress
     * @return mixed
     */
    private function createBillingAddress($orderBillingAddress)
    {
        $billingAddress = [
            'gender' => $orderBillingAddress->gender,
            'name1' => $orderBillingAddress->name,
            'address1' => $orderBillingAddress->address,
            'postalCode' => $orderBillingAddress->postal_code,
            'town' => $orderBillingAddress->city,
            'countryId' => $orderBillingAddress->country_id
        ];
        return $this->AddressRepository->createAddress($billingAddress)->id;
    }



    private function saveOrder($order)
    {
        $data = [
            'typeId' => 1, // sales order
            'methodOfPaymentId' => 1,
            'shippingProfileId' => 1,
            'paymentStatus' => 1,
            'statusId' => 5,
            'plentyId' => $this->plentyId,
            'addressRelations' => [
                [
                    'typeId' => self::BILLING_ADDRESS,
                    'addressId' => $this->createBillingAddress($order->billing_address)
                ],
                [
                    'typeId' => self::DELIVERY_ADDRESS,
                    'addressId' => $this->createDeliveryAddress($order->reference_key, $order->delivery_address)
                ]
            ]
        ];

        $orderItems = [];
        foreach($order['products'] as $productDetails)
        {
            $orderItems[] = [
                'typeId' => 1,
                'itemVariationId' => str_replace('U1-', '', $productDetails->itemVariationId),
                'quantity' => $productDetails->quantity,
                'orderItemName' => $productDetails->productTitle,
                'amounts' => [
                    0 => [
                        'isSystemCurrency' => true,
                        'isNet' => true,
                        'exchangeRate' => 1,
                        'currency' => 'EUR',
                        'priceOriginalGross' => $productDetails->price
                    ]
                ]
            ];
        }

        $data['orderItems'] = $orderItems;
        $orderData = $this->OrderRepository->createOrder($data);
        $this->saveOrderData($order->reference_key, $orderData->id);

    }


    private function getPlentyPluginInfo()
    {
        /** @var Application $plentyId */
        $plentyId = pluginApp(Application::class);

        return $plentyId->getPlentyId();
    }

    /**
     * @param $referenceId
     * @param $plentyOrderId
     */
    private function saveOrderData($referenceId, $plentyOrderId)
    {
        $settingsHelper = pluginApp(SettingsHelper::class);
        $orderData = [
            $referenceId => $plentyOrderId
        ];
        if($settingsHelper->get(SettingsHelper::ORDER_DATA) === null) {
            $settingsHelper->set(SettingsHelper::ORDER_DATA, $orderData);
        } else {
            $settingsHelper->set(SettingsHelper::ORDER_DATA, array_merge($orderData, $settingsHelper->get(SettingsHelper::ORDER_DATA)));
        }
    }
}