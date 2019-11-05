<?php

namespace PandaBlack\Repositories;

use PandaBlack\Contracts\CategoriesRepositoryContract;
use PandaBlack\Models\Category;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;

class CategoriesRepository implements CategoriesRepositoryContract
{
    /**
     * @var DataBase
     */
    private $database;

    /**
     * CategoriesRepository constructor.
     */
    public function __construct()
    {
        $this->database = pluginApp(DataBase::class);
    }

    /**
     * @param array $data
     * @return Category
     */
    public function createCategory(array $data): Category
    {
        $category = pluginApp(Category::class);

        $categoryData = $this->database->query(Category::class)
            ->where('category_identifier', '=', $data['categoryId'])->get();

        if(count($categoryData) <= 0 || $categoryData === null) {
            $category->category_identifier = $data['categoryId'];
            $category->tree_path = $data['treePath'];

            $this->database->save($category);

            return $category;
        } else {
            return $categoryData[0];
        }
    }


    /**
     * @param $id
     * @param $categoryTreePath
     * @return Category
     */
    public function updateCategory($id, $categoryTreePath): Category
    {
        $categoryData = $this->database->query(Category::class)
            ->where('category_identifier', '=', $id)->get();
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
        $categoryData = $this->database->query(Category::class)
            ->where('category_identifier', '=', $id)->get();

        return $categoryData;
    }

    /**
     * @param $id
     * @return Category
     */
    public function deleteCategory($id): Category
    {
        $categoryData = $this->database->query(Category::class)
            ->where('category_identifier', '=', $id)->get();

        $category = $categoryData[0];
        $this->database->delete($category);

        return $category;
    }


    /**
     * @return array
     */
    public function getCategories()
    {
        $categoryTree = [];

        $categories = $this->database->query(Category::class)
            ->where('id' , '!=', 'null')->get();

        foreach($categories as $category)
        {
            $categoryTree[$category->category_identifier] = $category->tree_path;
        }

        return $categoryTree;
    }
}