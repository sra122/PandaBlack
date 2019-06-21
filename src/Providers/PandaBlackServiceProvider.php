<?php

namespace PandaBlack\Providers;

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

        $eventProceduresService->registerProcedure('pandablack', ProcedureEntry::PROCEDURE_GROUP_SHIPPING, [
            'de' => 'Versandbestätigung an PandaBlack senden',
            'en' => 'Send shipping notification to Etsy'
        ], 'PandaBlack\\Procedures\\ShippingNotificationEventProcedure@run');
    }
}
