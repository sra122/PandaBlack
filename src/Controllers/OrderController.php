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

    public function createOrder()
    {
        $app = pluginApp(AppController::class);
        $orders = $app->authenticate('pandaBlack_orders');

        if(!empty($orders)) {
            $this->OrderRepository = pluginApp(OrderRepositoryContract::class);
            $this->AddressRepository = pluginApp(AddressRepositoryContract::class);
            $plentyId = $this->getPlentyPluginInfo();
            $billingAddressId = $this->Settings->get('pb_billing_address_id');

            if(empty($billingAddressId)) {
                $billingAddressId = $this->createBillingAddress();
            }

            foreach($orders as $order)
            {
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
                            'addressId' => $billingAddressId
                        ],
                        [
                            'typeId' => self::DELIVERY_ADDRESS,
                            'addressId' => $this->createDeliveryAddress($order['reference_key'])->id
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
                $this->OrderRepository->createOrder($data);
            }
        }
    }


    private function getPlentyPluginInfo()
    {
        /** @var Application $plentyId */
        $plentyId = pluginApp(Application::class);

        return $plentyId->getPlentyId();
    }


    private function createDeliveryAddress($referenceKey)
    {
        $deliveryAddress = [
            'gender' => 'male',
            'name1' => 'PANDA.BLACK GmbH',
            'address1' => 'Friedrichstraße 123',
            'address2' => 'Ref Id ' . $referenceKey,
            'postalCode' => '10711',
            'town' => 'Berlin',
            'countryId' => 1
        ];

        return $this->AddressRepository->createAddress($deliveryAddress);
    }


    private function createBillingAddress()
    {
        $addressRepository = pluginApp(AddressRepositoryContract::class);

        $billingAddress = [
            'gender' => 'male',
            'name1' => 'PANDA.BLACK GmbH',
            'address1' => 'Friedrichstraße 123',
            'postalCode' => '10711',
            'town' => 'Berlin',
            'countryId' => 1
        ];

        $address = $addressRepository->createAddress($billingAddress);
        $this->Settings->set('pb_billing_address_id', $address->id);

        return $address->id;
    }
}