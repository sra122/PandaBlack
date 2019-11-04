<?php

namespace PandaBlack\Contracts;

use PandaBlack\Models\Categories;

interface CategoriesRepositoryContract
{
    /**
     * @param array $data
     * @return Categories
     */
    public function createCategory(array $data): Categories;


    /**
     * @param $id
     * @return array
     */
    public function getCategory($id): array;

    /**
     * @param $id
     * @param $categoryTreePath
     * @return Categories
     */
    public function updateCategory($id, $categoryTreePath): Categories;


    /**
     * @param $id
     * @return Categories
     */
    public function deleteCategory($id): Categories;


    /**
     * @return array
     */
    public function getCategories();
}