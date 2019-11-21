<?php
namespace PandaBlack\Crons;
use PandaBlack\Controllers\AppController;
use Plenty\Modules\Cron\Contracts\CronHandler as Cron;
class PluginVersionCron extends Cron
{
    public function handle()
    {
        $appController = pluginApp(AppController::class);
        $appController->authenticate('pandaBlack_version');
    }
}