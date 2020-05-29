<?php
/**
 * Created by PhpStorm.
 * User: sravan
 * Date: 13.06.19
 * Time: 12:07
 */

namespace PandaBlack\Controllers;

use PandaBlack\Helpers\PaymentHelper;
use PandaBlack\Repositories\NotificationsRepository;
use PandaBlack\Repositories\PropertiesRepository;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;
use Plenty\Plugin\Controller;

class NotificationController extends Controller
{
    private $notifications;

    public function __construct(NotificationsRepository $notifications)
    {
        $this->notifications = $notifications;
    }

    /**
     * @return mixed
     */
    public function fetchNotifications()
    {
        $this->createNotification();
        return $this->notifications->getNotifications();
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