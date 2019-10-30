<?php

namespace PandaBlack\Migrations;


use PandaBlack\Models\PbCategories;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;

class CreatePBCategoriesTable
{
    public function run(Migrate $migrate)
    {
        $migrate->createTable(PbCategories::class);
    }
}