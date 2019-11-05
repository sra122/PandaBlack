<?php

namespace PandaBlack\Repositories;

use PandaBlack\Contracts\NotificationsRepositoryContract;
use PandaBlack\Models\Notification;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;

class NotificationsRepository implements NotificationsRepositoryContract
{
    /**
     * @var DataBase
     */
    private $database;

    /**
     * CategoriesRepository constructor.
     */
    public function __construct()
    {
        $this->database = pluginApp(DataBase::class);
    }

    /**
     * @param array $data
     * @return Notification
     */
    public function createNotification(array $data): Notification
    {
        $notification = pluginApp(Notification::class);

        $notificationData = $this->database->query(Notification::class)
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
        $notifications = $this->database->query(Notification::class)
            ->where('read', '=', false)
            ->get();

        return $notifications;
    }

    /**
     * @param $id
     * @return Notification
     */
    public function markAsRead($id): Notification
    {
        $notificationData = $this->database->query(Notification::class)
            ->where('notification_identifier', '=', $id)
            ->get();

        $notification = $notificationData[0];
        $notification->read = true;
        $this->database->save($notification);

        return $notification;
    }

}