<?php
namespace PandaBlack\Migrations;
use PandaBlack\Models\Categories;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;
class CreatePBCategoriesTable
{
    public function run(Migrate $migrate)
    {
        $migrate->createTable(Categories::class);
    }
}