<?php
namespace PandaBlack\Migrations;

use PandaBlack\Helpers\SettingsHelper;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;

class PandaBlackAddress
{
    /** @var SettingsHelper */
    protected $Settings;

    public function __construct(SettingsHelper $SettingsHelper)
    {
        $this->Settings = $SettingsHelper;
    }

    public function run()
    {
        /** @var AddressRepositoryContract $addressRepository */
        $addressRepository = pluginApp(AddressRepositoryContract::class);

        $billingAddress = [
            'gender' => 'male',
            'name1' => 'PANDA.BLACK GmbH',
            'address1' => 'Friedrichstraße 123',
            'postalCode' => '10711',
            'town' => 'Berlin',
            'countryId' => 1
        ];

        $address = $addressRepository->createAddress($billingAddress);
        $this->Settings->set('pb_billing_address_id', $address->id);
    }
}