<?php

namespace PandaBlack\Contracts;

use PandaBlack\Models\Category;

interface CategoriesRepositoryContract
{
    /**
     * @param array $data
     * @return Category
     */
    public function createCategory(array $data): Category;


    /**
     * @param $id
     * @return array
     */
    public function getCategory($id): array;

    /**
     * @param $id
     * @param $categoryTreePath
     * @return Category
     */
    public function updateCategory($id, $categoryTreePath): Category;


    /**
     * @param $id
     * @return Category
     */
    public function deleteCategory($id): Category;


    /**
     * @return array
     */
    public function getCategories();
}