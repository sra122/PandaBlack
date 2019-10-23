<?php

namespace PandaBlack\Crons;

use PandaBlack\Controllers\AppController;
use Plenty\Modules\Cron\Contracts\CronHandler as Cron;

class PluginVersionCron extends Cron
{
    public function __construct(AppController $appController)
    {
        $appController->authenticate('pandaBlack_version');
    }
}