<?php
namespace PandaBlack\Migrations;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;
class DeleteAttributesTable
{
    public function run(Migrate $migrate)
    {
        $migrate->deleteTable('PandaBlack\Models\Attributes');
    }
}