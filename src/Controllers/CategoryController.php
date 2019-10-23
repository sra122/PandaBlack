<?php

namespace PandaBlack\Controllers;

use PandaBlack\Helpers\SettingsHelper;
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
        $app = pluginApp(AppController::class);
        return $app->authenticate('pandaBlack_categories');
    }
}
