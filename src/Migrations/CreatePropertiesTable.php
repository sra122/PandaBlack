<?php
namespace PandaBlack\Migrations;
use PandaBlack\Helpers\SettingsHelper;
use PandaBlack\Repositories\PropertiesRepository;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;
class CreatePropertiesTable
{
    public function run(Migrate $migrate)
    {
        $migrate->createTable('PandaBlack\Models\Property');
        $this->saveExistingProperties();
    }


    private function saveExistingProperties()
    {
        $settings = pluginApp(SettingsHelper::class);
        $propertiesRepo = pluginApp(PropertiesRepository::class);
        $properties = $settings->get(SettingsHelper::MAPPING_INFO);

        if (isset($properties['property']) && count($properties['property']) > 0) {
            foreach($properties['property'] as $key => $property)
            {
                $propertyData = [
                    'type' => 'property',
                    'value' => $property,
                    'key' => $key
                ];

                $propertiesRepo->createProperty($propertyData);
            }
        }

        if (isset($properties['propertyValue']) && count($properties['propertyValue']) > 0) {
            foreach($properties['propertyValue'] as $key => $propertyValue)
            {
                $propertyValueData = [
                    'type' => 'propertyValue',
                    'value' => $propertyValue,
                    'key' => $key
                ];

                $propertiesRepo->createProperty($propertyValueData);
            }
        }
    }
}