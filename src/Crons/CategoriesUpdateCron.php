<?php

namespace PandaBlack\Crons;

use PandaBlack\Controllers\CategoryController;
use Plenty\Modules\Cron\Contracts\CronHandler as Cron;

class CategoriesUpdateCron extends Cron
{
    public function __construct(CategoryController $categoryController)
    {
        $categoryController->updateCategoriesInPM();
    }
}