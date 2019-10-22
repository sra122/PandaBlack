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

    public function __construct(SettingsHelper $SettingsHelper)
    {
        $this->Settings = $SettingsHelper;
    }


    /**
     *
     */
    public function createOrder()
    {
        $app = pluginApp(AppController::class);
        $orders = $app->authenticate('pandaBlack_orders');

        if(!empty($orders)) {
            $ordersInfo = [];
            $this->OrderRepository = pluginApp(OrderRepositoryContract::class);
            $this->AddressRepository = pluginApp(AddressRepositoryContract::class);
            $plentyId = $this->getPlentyPluginInfo();
            $ordersData = $settingsHelper = pluginApp(SettingsHelper::class);
            $existingOrders = $settingsHelper->get(SettingsHelper::ORDERS);

            if($existingOrders !== null)
            {
                foreach($existingOrders as $existingOrder)
                {
                    $ordersInfo[$existingOrder['referenceId']] = $existingOrder['plentyOrderId'];
                }

                foreach($orders as $order)
                {
                    if(!isset($ordersInfo[$order['reference_key']])) {
                        $data = [
                            'typeId' => 1, // sales order
                            'methodOfPaymentId' => 1,
                            'shippingProfileId' => 1,
                            'paymentStatus' => 1,
                            'statusId' => 5,
                            'plentyId' => $plentyId,
                            'addressRelations' => [
                                [
                                    'typeId' => self::BILLING_ADDRESS,
                                    'addressId' => $this->createBillingAddress($order['billing_address'])
                                ],
                                [
                                    'typeId' => self::DELIVERY_ADDRESS,
                                    'addressId' => $this->createDeliveryAddress($order['reference_key'], $order['delivery_address'])
                                ]
                            ]
                        ];

                        $orderItems = [];
                        foreach($order['products'] as $productDetails)
                        {
                            $orderItems[] = [
                                'typeId' => 1,
                                'itemVariationId' => $productDetails['itemVariationId'],
                                'quantity' => $productDetails['quantity'],
                                'orderItemName' => $productDetails['productTitle'],
                                'amounts' => [
                                    0 => [
                                        'isSystemCurrency' => true,
                                        'isNet' => true,
                                        'exchangeRate' => 1,
                                        'currency' => 'EUR',
                                        'priceOriginalGross' => $productDetails['price']
                                    ]
                                ]
                            ];
                        }

                        $data['orderItems'] = $orderItems;
                        $order = $this->OrderRepository->createOrder($data);
                        $this->saveOrderData($order['reference_key'], $order->id);
                    }
                }
            }
        }
    }


    /**
     * @return int
     */
    private function getPlentyPluginInfo()
    {
        /** @var Application $plentyId */
        $plentyId = pluginApp(Application::class);

        return $plentyId->getPlentyId();
    }


    /**
     * @param $referenceKey
     * @param $orderDeliveryAddress
     * @return mixed
     */
    private function createDeliveryAddress($referenceKey, $orderDeliveryAddress)
    {
        $deliveryAddress = [
            'gender' => $orderDeliveryAddress['gender'],
            'name1' => $orderDeliveryAddress['name'],
            'address1' => $orderDeliveryAddress['address'],
            'address2' => 'Ref Id ' . $referenceKey,
            'postalCode' => $orderDeliveryAddress['postal_code'],
            'town' => $orderDeliveryAddress['city'],
            'countryId' => $orderDeliveryAddress['country_id']
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
            'gender' => $orderBillingAddress['gender'],
            'name1' => $orderBillingAddress['name'],
            'address1' => $orderBillingAddress['address'],
            'postalCode' => $orderBillingAddress['postal_code'],
            'town' => $orderBillingAddress['city'],
            'countryId' => $orderBillingAddress['country_id']
        ];

        return $this->AddressRepository->createAddress($billingAddress)->id;
    }


    /**
     * @param $referenceId
     * @param $plentyOrderId
     */
    private function saveOrderData($referenceId, $plentyOrderId)
    {
        $settingsHelper = pluginApp(SettingsHelper::class);
        $orderData = [
            'referenceId' => $referenceId,
            'plentyOrderId' => $plentyOrderId
        ];

        if($settingsHelper->get(SettingsHelper::ORDERS) === null) {
            $settingsHelper->set(SettingsHelper::ORDERS, $orderData);
        } else {
            array_merge($orderData, $settingsHelper->get(SettingsHelper::ORDERS));
        }
    }

}