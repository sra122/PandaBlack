<?php

namespace PandaBlack\Crons;

use PandaBlack\Controllers\ContentController;
use Plenty\Modules\Cron\Contracts\CronHandler as Cron;

class ItemExportCron extends Cron
{
    public function handle()
    {
        $contentController = pluginApp(ContentController::class);
        $contentController->sendProductDetails(24);
    }
}