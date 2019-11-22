<?php
namespace PandaBlack\Controllers;
use PandaBlack\Helpers\SettingsHelper;
use PandaBlack\Repositories\OrdersRepository;
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

    /**
     * OrderController constructor.
     * @param SettingsHelper $SettingsHelper
     */
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
            $this->OrderRepository = pluginApp(OrderRepositoryContract::class);
            $this->AddressRepository = pluginApp(AddressRepositoryContract::class);

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
     * @return mixed
     */
    private function createDeliveryAddress($referenceKey, $orderDeliveryAddress)
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
            'postalCode' => (string)$orderBillingAddress['postal_code'],
            'town' => $orderBillingAddress['city'],
            'countryId' => $orderBillingAddress['country_id']
        ];
        return $this->AddressRepository->createAddress($billingAddress)->id;
    }


    /**
     * @param $order
     */
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
        $orderData = $this->OrderRepository->createOrder($data);
        $this->saveOrderData($order['reference_key'], $orderData->id);
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