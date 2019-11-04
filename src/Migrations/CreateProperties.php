<?php
namespace PandaBlack\Migrations;
use PandaBlack\Helpers\SettingsHelper;
use PandaBlack\Repositories\PropertiesRepository;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;
class CreateProperties
{
    public function run(Migrate $migrate)
    {
        $migrate->createTable('PandaBlack\Models\Properties');
        $this->saveExistingProperties();
    }


    private function saveExistingProperties()
    {
        $settings = pluginApp(SettingsHelper::class);
        $propertiesRepo = pluginApp(PropertiesRepository::class);
        $properties = $settings->get(SettingsHelper::MAPPING_INFO);

        foreach($properties->property as $property)
        {
            $propertyData = [
                'type' => 'property',
                'value' => $property
            ];

            $propertiesRepo->createProperty($propertyData);
        }


        foreach($properties->propertyValue as $propertyValue)
        {
            $propertyValueData = [
                'type' => 'propertyValue',
                'value' => $propertyValue
            ];

            $propertiesRepo->createProperty($propertyValueData);
        }
    }
}