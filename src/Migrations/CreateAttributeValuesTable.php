<?php
namespace PandaBlack\Migrations;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;
class CreateAttributeValuesTable
{
    public function run(Migrate $migrate)
    {
        $migrate->createTable('PandaBlack\Models\AttributeValues');
    }
}