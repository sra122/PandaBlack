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
use Plenty\Modules\Account\Contact\Models\ContactType;
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
        $this->createNotification();
        $this->createContact();
        /** @var ContactRepositoryContract $contactRepository */
        $contactRepository = pluginApp(ContactRepositoryContract::class);
        return $contactRepository->getContactList();
    }


    private function createContact()
    {
        /** @var ContactRepositoryContract $contactRepository */
        $contactRepository = pluginApp(ContactRepositoryContract::class);
        $data = [
            'email' => 'pandablack@i-way.net',
            'firstName' => 'Test First Name',
            'lastName' => 'Test Last Name',
            'referrerId' => 13,
            'plentyId' => 38447,
            'externalId' => 'Pb-contact-1',
            'typeId' => ContactType::TYPE_CUSTOMER
        ];
        $contactRepository->createContact($data);
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
            if(isset($notification['values']['message'])) {
                $notificationData = [
                    'notificationIdentifier' => (int)$key,
                    'message' => $notification['values']['message']
                ];

                $this->notifications->createNotification($notificationData);
            }
        }
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