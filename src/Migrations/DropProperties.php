<?php
namespace PandaBlack\Migrations;
use PandaBlack\Helpers\SettingsHelper;
use PandaBlack\Repositories\PropertiesRepository;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;
class DropProperties
{
    public function run(Migrate $migrate)
    {
        $migrate->deleteTable('PandaBlack\Models\Properties');
    }
}