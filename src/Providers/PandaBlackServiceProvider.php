<?php

namespace PandaBlack\Providers;

use PandaBlack\Contracts\CategoriesRepositoryContract;
use PandaBlack\Controllers\CategoryController;
use PandaBlack\Crons\CategoriesAndAttributesUpdateCron;
use PandaBlack\Crons\OrdersCron;
use PandaBlack\Repositories\CategoryRepository;
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
        $this->getApplication()->bind(CategoriesRepositoryContract::class, CategoryRepository::class);
    }

    /**
     * @param CronContainer $container
     * @param EventProceduresService $eventProceduresService
     */
    public function boot(CronContainer $container, EventProceduresService $eventProceduresService)
    {
        $container->add(CronContainer::DAILY, ItemExportCron::class);
        $container->add(CronContainer::HOURLY, OrdersCron::class);
        $container->add(CronContainer::DAILY, CategoriesAndAttributesUpdateCron::class);
        $container->add(CronContainer::DAILY, PluginVersionCron::class);

        // register event actions
        $eventProceduresService->registerProcedure('PandaBlack', ProcedureEntry::PROCEDURE_GROUP_ORDER, [
            'de' => 'Versandbestätigung an PandaBlack senden',
            'en' => 'Send shipping notification to PandaBlack'
        ], 'PandaBlack\\Procedures\\ShippingNotificationEventProcedure@run');
    }
}
