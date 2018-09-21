<?php

namespace PandaBlack\Crons;

use PandaBlack\Controllers\ContentController;
use Plenty\Modules\Cron\Contracts\CronHandler as Cron;

class ItemExportCron extends Cron
{
    public function __construct(ContentController $contentController)
    {
        $contentController->sendProductDetails();
    }
}