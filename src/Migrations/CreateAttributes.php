<?php
namespace PandaBlack\Migrations;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;
class CreateAttributes
{
    public function run(Migrate $migrate)
    {
        $migrate->createTable('PandaBlack\Models\Attribute');
    }
}