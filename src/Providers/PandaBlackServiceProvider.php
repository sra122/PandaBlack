<?php

namespace PandaBlack\Providers;

use PandaBlack\Controllers\CategoryController;
use PandaBlack\Crons\CategoriesAndAttributesUpdateCron;
use PandaBlack\Crons\OrdersCron;
use Plenty\Modules\EventProcedures\Services\Entries\ProcedureEntry;
use Plenty\Modules\EventProcedures\Services\EventProceduresService;
use Plenty\Plugin\ServiceProvider;
use Plenty\Modules\Cron\Services\CronContainer;
use PandaBlack\Crons\ItemExportCron;

class PandaBlackServiceProvider extends ServiceProvider
{
    /**
     * Register the core functions
     */
    public function register()
    {
        $this->getApplication()->register(PandaBlackRouteServiceProvider::class);
    }

    /**
     * @param CronContainer $container
     * @param EventProceduresService $eventProceduresService
     */
    public function boot(CronContainer $container, EventProceduresService $eventProceduresService)
    {
        /*$container->add(CronContainer::HOURLY, ItemExportCron::class);
        $container->add(CronContainer::HOURLY, OrdersCron::class);*/
        //$container->add(CronContainer::HOURLY, CategoriesUpdateCron::class);
        $container->add(CronContainer::HOURLY, CategoriesAndAttributesUpdateCron::class);


        $eventProceduresService->registerProcedure('pandablack', ProcedureEntry::PROCEDURE_GROUP_SHIPPING, [
            'de' => 'Versandbestätigung an PandaBlack senden',
            'en' => 'Send shipping notification to PandaBlack'
        ], 'PandaBlack\\Procedures\\ShippingNotificationEventProcedure@run');
    }
}
