<?php
namespace PandaBlack\Migrations;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;
class CreateAttributeValues
{
    public function run(Migrate $migrate)
    {
        $migrate->createTable('PandaBlack\Models\AttributeValues');
    }
}