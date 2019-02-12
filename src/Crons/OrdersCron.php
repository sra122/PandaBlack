<?php

namespace PandaBlack\Crons;

use PandaBlack\Controllers\OrderController;
use Plenty\Modules\Cron\Contracts\CronHandler as Cron;

class OrdersCron extends Cron
{
    public function __construct(OrderController $orderController)
    {
        $orderController->createOrder();
    }
}