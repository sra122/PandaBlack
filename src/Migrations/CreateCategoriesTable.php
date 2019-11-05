<?php
namespace PandaBlack\Migrations;
use PandaBlack\Controllers\CategoryController;
use PandaBlack\Repositories\CategoriesRepository;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;
class CreateCategoriesTable
{
    public function run(Migrate $migrate)
    {
        $migrate->createTable('PandaBlack\Models\Category');
        $this->saveCategories();
    }


    private function saveCategories()
    {
        $categoryRepo = pluginApp(CategoriesRepository::class);
        $categoryController = pluginApp(CategoryController::class);
        $categories = $categoryController->getPBCategoriesAsDropdown();

        foreach($categories as $key => $category)
        {
            if(!$category['is_deleted']) {
                $categoryData = [
                    'categoryId' => $key,
                    'treePath' => $category['name']
                ];

                $categoryRepo->createCategory($categoryData);
            }
        }
    }
}