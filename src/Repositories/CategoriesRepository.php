<?php

namespace PandaBlack\Repositories;

use PandaBlack\Contracts\CategoriesRepositoryContract;
use PandaBlack\Controllers\CategoryController;
use PandaBlack\Models\Attribute;
use PandaBlack\Models\AttributeValue;
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

        if (count($categoryData) <= 0 || $categoryData === null) {
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
        $categoryPlugin = pluginApp(Category::class);

        $categoryData = $this->database->query(Category::class)
            ->where('category_identifier', '=', $id)->get();

        if (count($categoryData) <= 0 || $categoryData === null) {
            $categoryPlugin->category_identifier = $id;
            $categoryPlugin->tree_path = $categoryTreePath;
            $this->database->save($categoryPlugin);

            return $categoryPlugin;
        } else {
            $category = $categoryData[0];

            $category->tree_path = $categoryTreePath;
            $this->database->save($category);

            return $category;
        }
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

        //Attributes
        $attributes = $this->database->query(Attribute::class)
            ->where('category_identifier', '=', $id)->get();

        foreach ($attributes as $attribute) {
            $this->database->delete($attribute);
        }

        //AttributeValues
        $attributeValues = $this->database->query(AttributeValue::class)
            ->where('category_identifier', '=', $id)->get();

        foreach ($attributeValues as $attributeValue) {
            $this->database->delete($attributeValue);
        }

        return $category;
    }


    /**
     * @return array
     */
    public function getCategories()
    {
        $categoryTree = [];

        $categories = $this->database->query(Category::class)
            ->where('id', '!=', 'null')->get();

        foreach ($categories as $category) {
            $categoryTree[$category->category_identifier] = $category->tree_path;
        }

        if(count($categoryTree) === 0) {
            $categoryController = pluginApp(CategoryController::class);
            $pbCategories = $categoryController->getPBCategoriesAsDropdown();

            foreach($pbCategories as $key => $pbCategory)
            {
                if(!$pbCategory['is_deleted']) {
                    $categoryData = [
                        'categoryId' => $key,
                        'treePath' => $pbCategory['name']
                    ];

                    $createdCategory = $this->createCategory($categoryData);
                    $categoryTree[$createdCategory->category_identifier] = $createdCategory->tree_path;
                }
            }
        }

        return $categoryTree;
    }


    public function deleteAll()
    {
        $categoryController = pluginApp(CategoryController::class);
        $pbCategories = $categoryController->getPBCategoriesAsDropdown();

        foreach($pbCategories as $key => $pbCategory)
        {
            $this->deleteCategory((int)$key);
        }

        return true;
    }
}