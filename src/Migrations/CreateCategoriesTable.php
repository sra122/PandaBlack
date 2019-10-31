<?php
namespace PandaBlack\Migrations;
use PandaBlack\Repositories\CategoryRepository;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;
class CreateCategoriesTable
{
    public function run(Migrate $migrate)
    {
        $migrate->createTable('PandaBlack\Models\Categories');
    }


    private function saveCategories()
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
}