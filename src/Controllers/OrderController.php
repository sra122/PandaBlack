<?php

namespace PandaBlack\Controllers;

use PandaBlack\Helpers\PaymentHelper;
use PandaBlack\Helpers\PBApiHelper;
use PandaBlack\Helpers\SettingsHelper;
use PandaBlack\Repositories\OrdersRepository;
use Plenty\Modules\Account\Address\Models\AddressRelationType;
use Plenty\Modules\Account\Contact\Contracts\ContactAddressRepositoryContract;
use Plenty\Modules\Account\Contact\Contracts\ContactRepositoryContract;
use Plenty\Modules\Account\Contact\Models\ContactType;
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
    /** @var SettingsHelper */
    protected $Settings;
    /** @var ContactRepositoryContract */
    protected $ContactRepository;
    /** @var AppController */
    protected $App;
    /** @var PaymentHelper */
    protected $PaymentHelper;
    protected $plentyId;

    /**
     * OrderController constructor.
     * @param SettingsHelper $SettingsHelper
     */
    public function __construct(SettingsHelper $SettingsHelper)
    {
        $this->Settings = $SettingsHelper;
        $this->PaymentHelper = pluginApp(PaymentHelper::class);
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

    public function createOrder()
    {
        $this->App = pluginApp(AppController::class);
        $orders = $this->App->authenticate('pandaBlack_orders');
        if (!empty($orders)) {
            $this->OrderRepository = pluginApp(OrderRepositoryContract::class);
            $this->ContactRepository = pluginApp(ContactRepositoryContract::class);
            $this->ContactAddressRepository = pluginApp(ContactAddressRepositoryContract::class);
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
        $contactId = $this->getContact($order['contact_details']);
        if ($contactId !== null) {
            $data = [
                'typeId' => $order['type_id'],
                'statusId' => $order['status_id'],
                'plentyId' => $this->plentyId,
                'referrerId' => $this->Settings->get('orderReferrerId'),
                'addressRelations' => [
                    [
                        'typeId' => self::BILLING_ADDRESS,
                        'addressId' => $this->createBillingAddress($order['billing_address'], $contactId)
                    ],
                    [
                        'typeId' => self::DELIVERY_ADDRESS,
                        'addressId' => $this->createDeliveryAddress($order['reference_key'], $order['delivery_address'], $contactId)
                    ]
                ],
                'relations' => [
                    [
                        'referenceType' => 'contact',
                        'referenceId' => $contactId,
                        'relation' => 'receiver',
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
            try {
                $orderData = $this->OrderRepository->createOrder($data);
                $this->saveOrderData($order['reference_key'], $orderData->id);
                $orderInfo = [
                    'external_identifier' => $orderData->id,
                    'order_reference' => $order['reference_key']
                ];
                $this->App->logInfo(PBApiHelper::ORDER_CREATE, $orderInfo);
            } catch (\Exception $e) {
                $this->App->logInfo(PBApiHelper::ORDER_ERROR, $e->getMessage());
            }
        } else {
            $this->App->logInfo('Null response', $contactId);
        }
    }

    /**
     * @param $contactDetails
     * @return mixed
     */
    private function getContact($contactDetails)
    {
        try {
            $contactId = $this->ContactRepository->getContactByOptionValue($contactDetails['email'], 2, 4)->id;
            if ($contactId === null) {
                $contactData = [
                    'email' => $contactDetails['email'],
                    'firstName' => $contactDetails['first_name'],
                    'lastName' => $contactDetails['last_name'],
                    'referrerId' => $this->Settings->get('orderReferrerId'),
                    'plentyId' => $this->plentyId,
                    'typeId' => ContactType::TYPE_CUSTOMER,
                    'options' => [
                        [
                            'typeId' => 2,
                            'subTypeId' => 4,
                            'value' => $contactDetails['email']
                        ]
                    ]
                ];
                try {
                    return $this->ContactRepository->createContact($contactData)->id;
                } catch (\Exception $e) {
                    $this->App->logInfo(PBApiHelper::CREATE_CONTACT, json_encode($e, JSON_PRETTY_PRINT));
                }
            }
            return $contactId;
        } catch (\Exception $e) {
            $this->App->logInfo(PBApiHelper::CONTACT_CREATION_ERROR, json_encode($e, JSON_PRETTY_PRINT));
        }
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
        $ordersRepo = pluginApp(OrdersRepository::class);

        $orderData = [
            'referenceKey' => $referenceId,
            'order_id' => $plentyOrderId
        ];

        $ordersRepo->createOrder($orderData);
    }
}