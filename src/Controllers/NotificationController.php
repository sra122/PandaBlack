<?php
/**
 * Created by PhpStorm.
 * User: sravan
 * Date: 13.06.19
 * Time: 12:07
 */

namespace PandaBlack\Controllers;

use PandaBlack\Repositories\NotificationsRepository;
use PandaBlack\Repositories\PropertiesRepository;
use Plenty\Modules\Account\Contact\Contracts\ContactRepositoryContract;
use Plenty\Plugin\Controller;

class NotificationController extends Controller
{
    private $notifications;
    /** @var ContactRepositoryContract */
    protected $ContactRepository;
    /** @var OrderController */
    protected $OrderController;

    public function __construct(NotificationsRepository $notifications)
    {
        $this->notifications = $notifications;
        $this->ContactRepository = pluginApp(ContactRepositoryContract::class);
        $this->OrderController = pluginApp(OrderController::class);
    }

    /**
     * @return mixed
     */
    public function fetchNotifications()
    {
        //$this->createNotification();
        //$this->fetchContactDetails();
        //$this->OrderController->createOrder();
        return $this->OrderController->createOrder();
    }


    public function fetchContactDetails()
    {
        return $this->ContactRepository->getContactByOptionValue('pandablack@i-ways.net', 2, 4);
        //return $this->ContactRepository->getContactIdByEmail('pandablack@i-ways.net');
    }

    public function createNotification()
    {
        $app = pluginApp(AppController::class);
        $notifications = $app->authenticate('pandaBlack_notifications');

        foreach ($notifications as $key => $notification) {
            if (isset($notification['values']['message'])) {
                $notificationData = [
                    'notificationIdentifier' => (int)$key,
                    'message' => $notification['values']['message']
                ];

                $this->notifications->createNotification($notificationData);
            }
        }
    }

    public function markAsRead($id)
    {
        $this->notifications->markAsRead($id);
    }

    public function fetchProductsStatus()
    {
        $app = pluginApp(AppController::class);
        return $app->authenticate('pandaBlack_products_status');
    }


    public function getProperties()
    {
        $propertyRepo = pluginApp(PropertiesRepository::class);
        return $propertyRepo->getProperties();
    }
}