<?php
namespace PandaBlack\Controllers;
use PandaBlack\Helpers\PaymentHelper;
use PandaBlack\Helpers\SettingsHelper;
use PandaBlack\Repositories\OrdersRepository;
use Plenty\Modules\Account\Address\Models\AddressRelationType;
use Plenty\Modules\Account\Contact\Contracts\ContactAddressRepositoryContract;
use Plenty\Modules\Account\Contact\Contracts\ContactRepositoryContract;
use Plenty\Modules\Account\Contact\Models\ContactType;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;
use Plenty\Plugin\Controller;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Plugin\Application;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
class OrderController extends Controller
{
    /** @var OrderRepositoryContract */
    protected $OrderRepository;
    /** @var ContactAddressRepositoryContract */
    protected $ContactAddressRepository;
    const BILLING_ADDRESS = 1;
    const DELIVERY_ADDRESS = 2;
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


    public function createOrder()
    {
        $this->App = pluginApp(AppController::class);
        $orders = $this->App->authenticate('pandaBlack_orders');
        if(!empty($orders)) {
            $this->OrderRepository = pluginApp(OrderRepositoryContract::class);
            $this->ContactRepository = pluginApp(ContactRepositoryContract::class);
            $this->ContactAddressRepository = pluginApp(ContactAddressRepositoryContract::class);
            $ordersRepo = pluginApp(OrdersRepository::class);
            $orderReferenceKeys = $ordersRepo->getReferenceKeys();

            foreach($orders['orders'] as $order)
            {
                if(!isset($orderReferenceKeys[$order['reference_key']])) {
                    $this->saveOrder($order);
                }
            }
        }
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
     * @param $order
     */
    private function saveOrder($order)
    {
        $contactId = $this->getContact($order['contact_details']);
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
                    'referenceId'   => $contactId,
                    'relation'      => 'receiver',
                ]
            ],/*
            'properties' => [
                [
                    'typeId' => OrderPropertyType::PAYMENT_METHOD,
                    'value'  => (string)$this->PaymentHelper->getPaymentMethodId(),
                ]
            ]*/
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
        $orderData = $this->OrderRepository->createOrder($data);
        $this->saveOrderData($order['reference_key'], $orderData->id);
    }

    /**
     * @param $contactDetails
     * @return mixed
     */
    private function getContact($contactDetails)
    {
        $contactId = $this->ContactRepository->getContactIdByEmail($contactDetails['email']);
        if ($contactId === null) {
            $contactData = [
                'email' => $contactDetails['email'],
                'firstName' => $contactDetails['first_name'],
                'lastName' => $contactDetails['last_name'],
                'referrerId' => $this->Settings->get('orderReferrerId'),
                'plentyId' => $this->plentyId,
                'typeId' => ContactType::TYPE_CUSTOMER
            ];
            try {
                return $this->ContactRepository->createContact($contactData)->id;
            } catch (\Exception $e) {
                $this->App->logInfo('createContact', $e->getMessage());
            }
        }
        return $contactId;
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