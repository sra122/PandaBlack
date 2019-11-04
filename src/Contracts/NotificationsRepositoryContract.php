<?php

namespace PandaBlack\Contracts;

use PandaBlack\Models\Notifications;

interface NotificationsRepositoryContract
{
    /**
     * @param array $data
     * @return Notifications
     */
    public function createNotification(array $data): Notifications;

    /**
     * @return array
     */
    public function getNotifications(): array;
}