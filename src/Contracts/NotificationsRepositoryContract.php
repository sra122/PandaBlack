<?php

namespace PandaBlack\Contracts;

use PandaBlack\Models\Notification;

interface NotificationsRepositoryContract
{
    /**
     * @param array $data
     * @return Notification
     */
    public function createNotification(array $data): Notification;

    /**
     * @return array
     */
    public function getNotifications(): array;

    /**
     * @param $id
     * @return Notification
     */
    public function markAsRead($id): Notification;
}