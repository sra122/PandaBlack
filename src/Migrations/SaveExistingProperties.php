<?php
namespace PandaBlack\Migrations;
use PandaBlack\Helpers\SettingsHelper;
use PandaBlack\Repositories\PropertiesRepository;
class SaveExistingProperties
{
    public function run()
    {
        $settings = pluginApp(SettingsHelper::class);
        $propertiesRepo = pluginApp(PropertiesRepository::class);
        $properties = $settings->get(SettingsHelper::MAPPING_INFO);

        foreach($properties['property'] as $property)
        {
            $propertyData = [
                'type' => 'property',
                'value' => $property
            ];

            $propertiesRepo->createProperty($propertyData);
        }


        foreach($properties['propertyValue'] as $propertyValue)
        {
            $propertyValueData = [
                'type' => 'propertyValue',
                'value' => $propertyValue
            ];

            $propertiesRepo->createProperty($propertyValueData);
        }
    }
}