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


    public function deleteAllCategories()
    {
        $categoryRepo = pluginApp(CategoriesRepository::class);
        return $categoryRepo->deleteAll();
    }


    public function getCategoriesList()
    {
        $categoryRepo = pluginApp(CategoriesRepository::class);
        $categories = $categoryRepo->getCategories();

        if(count($categories) <= 0) {
            $pbCategories = $this->getPBCategoriesAsDropdown();
            foreach($pbCategories as $key => $pbCategory)
            {
                if(!$pbCategory['is_deleted']) {
                    $categoryData = [
                        'categoryId' => $key,
                        'treePath' => $pbCategory['name']
                    ];

                    $categoryRepo->createCategory($categoryData);
                }
            }

            return $categoryRepo->getCategories();
        } else {
            return $categories;
        }
    }
}
