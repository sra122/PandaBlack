<?php

namespace PandaBlack\Controllers;

use PandaBlack\Helpers\SettingsHelper;
use PandaBlack\Repositories\CategoryRepository;
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

        $categoryTree = [];

        if(isset($pbCategories)) {

            foreach($pbCategories as $l1 => $l1Category)
            {
                if($l1Category['parent_id'] === null)
                {
                    foreach($pbCategories as $l2 => $l2Category) {
                        $set = false;
                        if($l2Category['parent_id'] == $l1)
                        {
                            foreach($pbCategories as $l3 => $l3Category)
                            {
                                if($l3Category['parent_id'] == $l2)
                                {

                                    foreach($pbCategories as $l4 => $child3Category) {
                                        if($l4 == $l3) {
                                            $set = true;
                                        }
                                    }

                                    if($set && ($l1Category['is_available'] && $l2Category['is_available'] && $l3Category['is_available'])) {
                                        $categoryTree[$l3] = $l1Category['name'] . ' > ' . $l2Category['name'] . ' > ' . $l3Category['name'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
            return $categoryTree;
        }
    }


    public function getCategoriesList()
    {
        $settingsHelper = pluginApp(SettingsHelper::class);
        $categoriesList = $settingsHelper->get(SettingsHelper::CATEGORIES_LIST);
        if(!empty($categoriesList)) {
            return $categoriesList;
        } else {
            $categoriesData = $this->getPBCategoriesAsDropdown();
            if(!empty($categoriesData)) {
                $this->savePBCategoriesInPM();
                return $categoriesData;
            }
        }
    }

    public function savePBCategoriesInPM()
    {
        $settingsHelper = pluginApp(SettingsHelper::class);

        $categoriesList = $settingsHelper->get(SettingsHelper::CATEGORIES_LIST);

        if(empty($categoriesList)) {
            $categoriesListAsDropdown = $this->getPBCategoriesAsDropdown();
            if(count($categoriesListAsDropdown) > 0) {
                $settingsHelper->set(SettingsHelper::CATEGORIES_LIST, $categoriesListAsDropdown);
            }
        }
    }


    public function saveCategoriesInDb()
    {
        $categoryRepo = pluginApp(CategoryRepository::class);
        $categories = $this->getPBCategoriesAsDropdown();

        foreach($categories as $key => $category)
        {
            $categoryData = [
                'categoryId' => $key,
                'treePath' => $category
            ];

            $categoryRepo->createCategory($categoryData);
        }
    }


    public function getCatetgories()
    {
        $categoryRepo = pluginApp(CategoryRepository::class);

        return $categoryRepo->getCategories();
    }
}
