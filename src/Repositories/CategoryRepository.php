<?php

namespace PandaBlack\Repositories;

use PandaBlack\Contracts\CategoriesRepositoryContract;
use PandaBlack\Models\Categories;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use Plenty\Modules\Plugin\DataBase\Contracts\Query;

class CategoryRepository implements CategoriesRepositoryContract
{
    /**
     * @var DataBase
     */
    private $database;

    /**
     * CategoryRepository constructor.
     */
    public function __construct()
    {
        $this->database = pluginApp(DataBase::class);
    }

    /**
     * @param array $data
     * @return Categories
     */
    public function createCategory(array $data): Categories
    {
        $category = pluginApp(Categories::class);

        $categoryData = $this->database->query(Categories::class)->where('category_identifier', '=', $data['categoryId'])->get();

        if(count($categoryData) <= 0 || $categoryData === null) {
            $category->category_identifier = $data['categoryId'];
            $category->tree_path = $data['treePath'];

            $this->database->save($category);

            return $category;
        }
    }


    /**
     * @param $id
     * @param $categoryTreePath
     * @return Categories
     */
    public function updateCategory($id, $categoryTreePath): Categories
    {
        $categoryData = $this->database->query(Categories::class)->where('category_identifier', '=', $id)->get();
        $category = $categoryData[0];

        $category->tree_path = $categoryTreePath;
        $this->database->save($category);

        return $category;
    }


    /**
     * @param $id
     * @return array
     */
    public function getCategory($id): array
    {
        $categoryData = $this->database->query(Categories::class)->where('category_identifier', '=', $id)->get();

        return $categoryData;
    }



    public function deleteCategory($id): Categories
    {
        $categoryData = $this->database->query(Categories::class)->where('category_identifier', '=', $id)->get();

        $category = $categoryData[0];
        $this->database->delete($category);

        return $category;
    }


    public function getCategories()
    {
        $categoryData = $this->database->query(Categories::class)->where('id' , '!=', 'null')->get();

        return $categoryData;
    }
}