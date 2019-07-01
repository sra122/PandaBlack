<?php

namespace PandaBlack\Crons;

use PandaBlack\Controllers\AttributeController;
use Plenty\Modules\Cron\Contracts\CronHandler as Cron;

class CategoriesAndAttributesUpdateCron extends Cron
{
    public function __construct(AttributeController $attributeController)
    {
        $attributeController->updatePBCategoriesAttributesInPM();
    }
}