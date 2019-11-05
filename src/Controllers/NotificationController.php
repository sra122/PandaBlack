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
use Plenty\Modules\Property\Contracts\PropertyNameRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertyRelationRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertyRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertySelectionRepositoryContract;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;

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
        return $this->notifications->getNotifications();
    }


    public function markAsRead($id)
    {
        $this->notifications->markAsRead($id);
    }


    public function createNotification()
    {
        $app = pluginApp(AppController::class);
        $notifications = $app->authenticate('pandaBlack_notifications');

        foreach($notifications as $key => $notification)
        {
            $notificationData = [
                'notificationIdentifier' => (int)$key,
                'message' => $notification['value']['message']
            ];

            $this->notifications->createNotification($notificationData);
        }
    }

    public function fetchProductErrors()
    {
        $app = pluginApp(AppController::class);
        return $app->authenticate('pandaBlack_product_errors');
    }


    public function getProperties()
    {
        $propertyRepo = pluginApp(PropertiesRepository::class);
        return $propertyRepo->getProperties();
    }
}