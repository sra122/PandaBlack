<?php

namespace PandaBlack\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Plugin\Application;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
class OrderController extends Controller
{
    const BILLING_ADDRESS = 1;
    const DELIVERY_ADDRESS = 2;

    public function createOrder()
    {
        /*$app = pluginApp(AppController::class);
        $orders = $app->authenticate('pandaBlack_orders');*/

        $orders = [
            0 => [
                'reference_key' => 'iecw12deo',
                'products' => [
                    0 => [
                        'itemVariationId' => 1081,
                        'quantity' => 1,
                        'price' => 749,
                        'productTitle' => 'Sofa Creme Classicline'
                    ]
                ]
            ]
        ];

        if(!empty($orders)) {

            foreach($orders[0] as $order)
            {

                return $order;
                /*$ordersRepo = pluginApp(OrderRepositoryContract::class);

                $data = [
                    'typeId' => 1, // sales order
                    'methodOfPaymentId' => 1,
                    'shippingProfileId' => 1,
                    'paymentStatus' => 1,
                    'statusId' => 1,
                    'statusName' => '',
                    'ownerId' => '',
                    'plentyId' => $this->getPlentyPluginInfo(),
                    'addressRelations' => [
                        [
                            'typeId' => self::BILLING_ADDRESS,
                            'addressId' => 1,//$this->createBillingAddress($order['reference_key'])->id
                        ],
                        [
                            'typeId' => self::DELIVERY_ADDRESS,
                            'addressId' => 2//$this->createDeliveryAddress($order['reference_key'])->id
                        ]
                    ]
                ];

                $orderItems = [];
                foreach($order['products'][0] as $productDetails)
                {
                    $orderItems[] = [
                        'typeId' => 1,
                        'itemVariationId' => $productDetails['itemVariationId'],
                        'quantity' => 1,
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

                return $orders;*/
            }
        }
    }


    private function getPlentyPluginInfo()
    {
        $plentyId = pluginApp(Application::class);

        return $plentyId->getPlentyId();
    }


    private function createDeliveryAddress($referenceKey)
    {
        $addressRepo = pluginApp(AddressRepositoryContract::class);
        $deliveryAddress = [
            'gender' => 'male',
            'name1' => 'PANDA.BLACK GmbH',
            'address1' => 'Friedrichstraße',
            'address2' => '123',
            'address3' => 'Order Id ' . $referenceKey,
            'postalCode' => '10711',
            'town' => 'Berlin',
            'countryId' => 1
        ];

        return $addressRepo->createAddress($deliveryAddress);
    }

    public function createBillingAddress($referenceKey)
    {
        $addressRepo = pluginApp(AddressRepositoryContract::class);
        $billingAddress = [
            'gender' => 'male',
            'name1' => 'PANDA.BLACK GmbH',
            'address1' => 'Friedrichstraße',
            'address2' => '123',
            'address3' => 'Bestellung Id ' . $referenceKey,
            'postalCode' => '10711',
            'town' => 'Berlin',
            'countryId' => 1
        ];

        return $addressRepo->createAddress($billingAddress);
    }
}