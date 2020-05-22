<?php

namespace PandaBlack\Controllers;

use PandaBlack\Helpers\SettingsHelper;
use PandaBlack\Repositories\OrdersRepository;
use Plenty\Modules\Account\Address\Models\AddressRelationType;
use Plenty\Modules\Account\Contact\Contracts\ContactAddressRepositoryContract;
use Plenty\Modules\Account\Contact\Contracts\ContactRepositoryContract;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Plugin\Application;
use Plenty\Plugin\Controller;

class OrderController extends Controller
{
    const BILLING_ADDRESS = 1;
    const DELIVERY_ADDRESS = 2;
    /** @var OrderRepositoryContract */
    protected $OrderRepository;
    /** @var ContactAddressRepositoryContract */
    protected $ContactAddressRepository;
    /** @var ContactRepositoryContract */
    protected $ContactRepository;
    /** @var SettingsHelper */
    protected $Settings;
    protected $plentyId;

    /**
     * OrderController constructor.
     * @param SettingsHelper $SettingsHelper
     */
    public function __construct(SettingsHelper $SettingsHelper)
    {
        $this->Settings = $SettingsHelper;
        $this->plentyId = $this->getPlentyPluginInfo();
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
     * Create Order
     */
    public function createOrder()
    {
        $app = pluginApp(AppController::class);
        $orders = $app->authenticate('pandaBlack_orders');
        if (!empty($orders)) {
            $this->OrderRepository = pluginApp(OrderRepositoryContract::class);
            $this->ContactAddressRepository = pluginApp(ContactAddressRepositoryContract::class);
            $this->ContactRepository = pluginApp(ContactRepositoryContract::class);

            $ordersRepo = pluginApp(OrdersRepository::class);
            $orderReferenceKeys = $ordersRepo->getReferenceKeys();

            foreach ($orders['orders'] as $order) {
                if (!isset($orderReferenceKeys[$order['reference_key']])) {
                    $this->saveOrder($order);
                }
            }
        }
    }

    /**
     * @param $order
     */
    private function saveOrder($order)
    {
        $contactId = $this->getContact($order['contactDetails']);
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
                    'addressId' => $this->createBillingAddress($order['billing_address'], $contactId)
                ],
                [
                    'typeId' => self::DELIVERY_ADDRESS,
                    'addressId' => $this->createDeliveryAddress($order['reference_key'], $order['delivery_address'], $contactId)
                ]
            ]
        ];
        $orderItems = [];
        foreach ($order['products'] as $productDetails) {
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
        $orderData = $this->OrderRepository->createOrder($data);
        $this->saveOrderData($order['reference_key'], $orderData->id);
    }

    /**
     * @param $contactDetails
     */
    private function getContact($contactDetails)
    {
        $contactId = $this->ContactRepository->getContactIdByEmail($contactDetails->email);

        if ($contactId === null) {
            $contactData = [
                'email' => $contactDetails->email,
                'firstName' => $contactDetails->first_name,
                'lastName' => $contactDetails->last_name,
                'referrerId' => $this->Settings->get('orderReferrerId'),
                'plentyId' => $this->plentyId
            ];

            return $this->ContactRepository->createContact($contactData)->id;
        }
        return $contactId;
    }

    /**
     * @param $orderBillingAddress
     * @param $contactId
     * @return mixed
     */
    private function createBillingAddress($orderBillingAddress, $contactId)
    {
        $billingAddress = [
            'gender' => $orderBillingAddress['gender'],
            'name1' => $orderBillingAddress['name'],
            'address1' => $orderBillingAddress['address'],
            'postalCode' => (string)$orderBillingAddress['postal_code'],
            'town' => $orderBillingAddress['city'],
            'countryId' => $orderBillingAddress['country_id']
        ];
        return $this->ContactAddressRepository->createAddress($billingAddress, $contactId, AddressRelationType::BILLING_ADDRESS)->id;
    }

    /**
     * @param $referenceKey
     * @param $orderDeliveryAddress
     * @param $contactId
     * @return mixed
     */
    private function createDeliveryAddress($referenceKey, $orderDeliveryAddress, $contactId)
    {
        $deliveryAddress = [
            'gender' => $orderDeliveryAddress['gender'],
            'name1' => $orderDeliveryAddress['name'],
            'address1' => $orderDeliveryAddress['address'],
            'address2' => 'Ref Id ' . $referenceKey,
            'postalCode' => (string)$orderDeliveryAddress['postal_code'],
            'town' => $orderDeliveryAddress['city'],
            'countryId' => $orderDeliveryAddress['country_id']
        ];
        return $this->ContactAddressRepository->createAddress($deliveryAddress, $contactId, AddressRelationType::DELIVERY_ADDRESS)->id;
    }

    /**
     * @param $referenceId
     * @param $plentyOrderId
     */
    private function saveOrderData($referenceId, $plentyOrderId)
    {
        /** @var OrdersRepository $ordersRepo */
        $ordersRepo = pluginApp(OrdersRepository::class);

        $orderData = [
            'referenceKey' => $referenceId,
            'order_id' => $plentyOrderId
        ];

        $ordersRepo->createOrder($orderData);
    }

    /**
     * @param $referenceId
     * @return \Plenty\Modules\Order\Models\Order|null
     */
    public function orderDetails($referenceId)
    {
        /** @var OrdersRepository $ordersRepo */
        $ordersRepo = pluginApp(OrdersRepository::class);
        $orderInfo = $ordersRepo->getOrderInfoWithReferenceKey($referenceId);

        if ($orderInfo !== null) {
            $this->OrderRepository = pluginApp(OrderRepositoryContract::class);
            return $this->OrderRepository->findOrderById($orderInfo['order_id']);
        }
        return null;
    }
}