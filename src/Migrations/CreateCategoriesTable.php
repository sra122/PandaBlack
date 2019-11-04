<?php
namespace PandaBlack\Migrations;
use PandaBlack\Controllers\CategoryController;
use PandaBlack\Repositories\CategoryRepository;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;
class CreateCategoriesTable
{
    public function run(Migrate $migrate)
    {
        $migrate->createTable('PandaBlack\Models\Categories');
        $this->saveCategories();
    }


    private function saveCategories()
    {
        $categoryRepo = pluginApp(CategoryRepository::class);
        $categoryController = pluginApp(CategoryController::class);
        $categories = $categoryController->getPBCategoriesAsDropdown();

        foreach($categories as $key => $category)
        {
            $categoryData = [
                'categoryId' => $key,
                'treePath' => $category
            ];

            $categoryRepo->createCategory($categoryData);
        }
    }
}