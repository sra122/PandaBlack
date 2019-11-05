<?php
namespace PandaBlack\Migrations;
use PandaBlack\Controllers\CategoryController;
use PandaBlack\Repositories\CategoriesRepository;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;
class CreateNotifications
{
    public function run(Migrate $migrate)
    {
        $migrate->createTable('PandaBlack\Models\Notification');
    }
}