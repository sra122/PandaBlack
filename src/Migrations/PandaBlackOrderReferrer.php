<?php
namespace PandaBlack\Migrations;

use PandaBlack\Helpers\SettingsHelper;
use Plenty\Modules\Order\Referrer\Contracts\OrderReferrerRepositoryContract;

class PandaBlackOrderReferrer
{
    public function __construct(SettingsHelper $SettingsHelper)
    {
        $this->Settings = $SettingsHelper;
    }

    public function run()
    {
        $orderReferrerId = $this->Settings->get('orderReferrerId');
        $orderReferrerExists = false;

        /** @var OrderReferrerRepositoryContract $orderReferrerRepositoryContract */
        $orderReferrerRepositoryContract = pluginApp(OrderReferrerRepositoryContract::class);

        if ($orderReferrerId !== null) {
            try {
                $orderReferrerRepositoryContract->getReferrerById($this->Settings->get('orderReferrerId'));
                $orderReferrerExists = true;
            } catch (\Exception $e) {}
        }

        if (!$orderReferrerExists) {
            /** @var array[] $orderReferrers */
            $orderReferrers = $orderReferrerRepositoryContract->getList(['id', 'name', 'backendName']);

            foreach ($orderReferrers as $orderReferrer) {
                if ($orderReferrer['name'] === 'PandaBlack' && $orderReferrer['backendName'] === 'PandaBlack') {
                    $this->Settings->set('orderReferrerId', $orderReferrer['id']);
                    $orderReferrerExists = true;
                    break;
                }
            }
        }

        if (!$orderReferrerExists) {
            $orderReferrer = $orderReferrerRepositoryContract->create([
                'isEditable'    => true,
                'backendName' => 'PandaBlack',
                'name'        => 'PandaBlack',
                'origin'      => 'plugin',
                'isFilterable' => true
            ]);
            $this->Settings->set('orderReferrerId', $orderReferrer->id);
        }
    }
}