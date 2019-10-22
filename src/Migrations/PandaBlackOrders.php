<?php
namespace PandaBlack\Migrations;

use PandaBlack\Helpers\SettingsHelper;
use Plenty\Modules\Order\Referrer\Contracts\OrderReferrerRepositoryContract;

class PandaBlackOrders
{
    /** @var SettingsHelper */
    protected $Settings;

    public function __construct(SettingsHelper $SettingsHelper)
    {
        $this->Settings = $SettingsHelper;
    }

    public function run()
    {
        $this->Settings->set(SettingsHelper::ORDERS, []);
    }
}