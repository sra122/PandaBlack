<?php

namespace PandaBlack\Crons;

use PandaBlack\Controllers\CategoryController;
use PandaBlack\Helpers\PBApiHelper;
use PandaBlack\Repositories\AttributesRepository;
use PandaBlack\Repositories\AttributeValuesRepository;
use PandaBlack\Repositories\CategoriesRepository;
use Plenty\Modules\Cron\Contracts\CronHandler as Cron;

class CategoriesAndAttributesUpdateCron extends Cron
{
    public function handle()
    {
        $this->updateCategories();
        $this->updateAttributes();
    }

    public function updateCategories()
    {
        $categoryController = pluginApp(CategoryController::class);
        $pbCategories = $categoryController->getPBCategoriesAsDropdown();

        $categoryRepo = pluginApp(CategoriesRepository::class);
        $savedCategories = $categoryRepo->getCategories();

        foreach($savedCategories as $savedCategory)
        {
            $categoryInfo = $pbCategories[$savedCategory->category_identifier];

            // Delete Category
            if($categoryInfo['is_deleted']) {
                $categoryRepo->deleteCategory((int)$savedCategory->category_identifier);
            } else {
                $categoryRepo->updateCategory((int)$savedCategory->category_identifier, $categoryInfo['name']);
            }
        }
    }


    public function updateAttributes()
    {
        $attributeRepo = pluginApp(AttributesRepository::class);
        $attributeValueRepo = pluginApp(AttributeValuesRepository::class);
        $pbApiHelper = pluginApp(PBApiHelper::class);
        $categories = $attributeRepo->getUniqueCategories();

        foreach($categories as $categoryId => $category)
        {
            $attributes = $pbApiHelper->fetchPBAttributes($category);

            foreach($attributes as $attributeIdentifier => $attribute)
            {
                if($attribute['required']) {
                    $attributeData = $attributeRepo->getAttribute((int)$attributeIdentifier);

                    // Delete Attribute
                    if($attribute['is_deleted']) {
                        $attributeRepo->deleteAttribute((int)$attributeIdentifier);
                        continue;
                    }

                    if(count($attributeData) > 0) {
                        foreach($attribute['values'] as $attributeValueIdentifier => $attributeValue)
                        {
                            $attributeValueData = $attributeValueRepo->getAttributeValue((int)$attributeValueIdentifier);

                            // Delete Attribute Value
                            if($attributeValue['is_deleted']) {
                                $attributeValueRepo->deleteAttributeValue((int)$attributeValueIdentifier);
                                continue;
                            }

                            if(count($attributeValueData) > 0) {
                                if($attributeValueData->name !== $attributeValue['name']) {
                                    $attributeValueRepo->updateAttributeValue((int)$attributeValueIdentifier, $attributeValue['name']);
                                }
                            } else {
                                $attributeValueCreateData = [
                                    'categoryId' => (int)$categoryId,
                                    'attributeId' => (int)$attributeIdentifier,
                                    'attributeValueName' => $attributeValue['name'],
                                    'attributeValueId' => (int)$attributeValueIdentifier
                                ];
                                $attributeValueRepo->createAttributeValue($attributeValueCreateData);
                            }
                        }
                    } else {
                        $attributeCreateData = [
                            'categoryId' => (int)$categoryId,
                            'attributeId' => (int)$attributeIdentifier,
                            'attributeName' => $attribute['name']
                        ];
                        $attributeRepo->createAttribute($attributeCreateData);

                        foreach($attribute['values'] as $attributeValueIdentifier => $attributeValue)
                        {
                            //If Value is not deleted
                            if(!$attributeValue['is_deleted']) {
                                $attributeValueCreateData = [
                                    'categoryId' => (int)$categoryId,
                                    'attributeId' => (int)$attributeIdentifier,
                                    'attributeValueName' => $attributeValue['name'],
                                    'attributeValueId' => (int)$attributeValueIdentifier
                                ];
                                $attributeValueRepo->createAttributeValue($attributeValueCreateData);
                            }
                        }
                    }
                }
            }
        }
    }
}