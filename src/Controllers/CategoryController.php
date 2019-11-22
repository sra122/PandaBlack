<?php

namespace PandaBlack\Controllers;

use PandaBlack\Helpers\SettingsHelper;
use PandaBlack\Repositories\CategoriesRepository;
use Plenty\Plugin\Controller;

/**
 * Class CategoryController
 * @package PandaBlack\Controllers
 */
class CategoryController extends Controller
{
    public $completeCategoryRepo = [];

    public function getPBCategoriesAsDropdown()
    {
        $app = pluginApp(AppController::class);
        $pbCategories = $app->authenticate('pandaBlack_categories');
        return $pbCategories;
    }


    public function getCategoriesList()
    {
        $categoryRepo = pluginApp(CategoriesRepository::class);
        return $categoryRepo->getCategories();
    }
}
