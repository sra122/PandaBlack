<?php

namespace PandaBlack\Repositories;

use PandaBlack\Contracts\NotificationsRepositoryContract;
use PandaBlack\Models\Notifications;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;

class NotificationsRepository implements NotificationsRepositoryContract
{
    /**
     * @var DataBase
     */
    private $database;

    /**
     * CategoryRepository constructor.
     */
    public function __construct()
    {
        $this->database = pluginApp(DataBase::class);
    }

    /**
     * @param array $data
     * @return Notifications
     */
    public function createNotification(array $data): Notifications
    {
        $notification = pluginApp(Notifications::class);

        $notificationData = $this->database->query(Notifications::class)
            ->where('notification_identifier', '=', $data['notificationIdentifier'])->get();

        if(count($notificationData) <= 0 || $notificationData === null) {
            $notification->notification_identifier = $data['notificationIdentifier'];
            $notification->message = $data['message'];
            $notification->time = time();
            $notification->read = false;
            $this->database->save($notification);

            return $notification;
        } else {
            return $notificationData[0];
        }
    }


    /**
     * @return array
     */
    public function getNotifications(): array
    {
        $propertyData = $this->database->query(Notifications::class)
            ->where('read', '=', false)
            ->get();

        return $propertyData;
    }
}