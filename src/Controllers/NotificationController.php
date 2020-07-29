<?php
/**
 * Created by PhpStorm.
 * User: sravan
 * Date: 13.06.19
 * Time: 12:07
 */

namespace PandaBlack\Controllers;

use PandaBlack\Helpers\SettingsHelper;
use PandaBlack\Repositories\NotificationsRepository;
use PandaBlack\Repositories\PropertiesRepository;
use Plenty\Modules\Account\Contact\Contracts\ContactRepositoryContract;
use Plenty\Modules\Item\VariationSku\Contracts\VariationSkuRepositoryContract;
use Plenty\Plugin\Controller;

class NotificationController extends Controller
{
    /** @var ContactRepositoryContract */
    protected $ContactRepository;
    /** @var OrderController */
    protected $OrderController;
    private $notifications;

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
        $this->createNotification();
        return $this->notifications->getNotifications();
    }

    /**
     * Create Notification
     */
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

    /**
     * @param $id
     */
    public function markAsRead($id)
    {
        $this->notifications->markAsRead($id);
    }

    /**
     * @return mixed
     */
    public function fetchProductsStatus()
    {
        $app = pluginApp(AppController::class);
        return $app->authenticate('pandaBlack_products_status');
    }

    /**
     * @return mixed
     */
    public function getProperties()
    {
        $propertyRepo = pluginApp(PropertiesRepository::class);
        return $propertyRepo->getProperties();
    }
}