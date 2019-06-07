<?php

namespace PandaBlack\Controllers;

use Plenty\Modules\Category\Models\Category;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;
use Plenty\Modules\Market\Settings\Contracts\SettingsRepositoryContract;
use Plenty\Modules\Market\Settings\Factories\SettingsCorrelationFactory;
use Plenty\Modules\Market\Credentials\Contracts\CredentialsRepositoryContract;
use Plenty\Modules\Category\Contracts\CategoryRepositoryContract;
/**
 * Class CategoryController
 * @package PandaBlack\Controllers
 */
class CategoryController extends Controller
{
    public $completeCategoryRepo = [];

    public function all(Request $request)
    {
        $with = $request->get('with', []);
        if (!is_array($with) && strlen($with)) {
            $with = explode(',', $with);
        }
        $categoryRepo = pluginApp(CategoryRepositoryContract::class);

        $pageNumber = 0;

        do {
            $categoryInfo = $categoryRepo->search($categoryId = null, $pageNumber++, 50, $with, ['type' => 'item', 'level' => 1]);
            $this->categoryChildMapping($categoryInfo->getResult());
        } while(!$categoryInfo->isLastPage());

        return $this->completeCategoryRepo;
    }


    private function categoryChildMapping($categoryInfo)
    {
        foreach($categoryInfo as $category)
        {
            if($category->parentCategoryId === null || $category->hasChildren) {
                $child = [];
                foreach($categoryInfo as $childCategory) {
                    if($childCategory->parentCategoryId === $category->id) {
                        array_push($child, $childCategory);
                    }
                }
                $category->child = $child;
            }

            if($category->parentCategoryId === null && $category->level === 1) {
                array_push($this->completeCategoryRepo, $category);
            }
        }
    }


    public function get(Request $request, Response $response, $id)
    {

        $categoryRepo = pluginApp(CategoryRepositoryContract::class);

        $category = $categoryRepo->get($id, $request->get('lang', 'de'));

        $plentyCategory = $category;

        $childCategoryName = $category->details[0]->name;

        while($category->parentCategoryId !== null) {
            $category = $categoryRepo->get($category->parentCategoryId);
            $category->details[0]->name = $category->details[0]->name . ' << ' . $childCategoryName ;
            $childCategoryName = $category->details[0]->name;
        }

        $parentCategoryPath = $category->details[0]->name;

        $plentyCategory->details[0]->name = $parentCategoryPath;

        return $response->json($plentyCategory);
    }


    public function getChild(Request $request, $id)
    {
        $categoryRepo = pluginApp(CategoryRepositoryContract::class);

        $childCategory = $categoryRepo->getChildren($id, $request->get('lang', 'de'));

        return $childCategory;
    }

    public function getCorrelations()
    {
        $filters = [
            'marketplaceId' => 'PandaBlack',
            'type' => 'category'
        ];

        $settingsCorrelationFactory = pluginApp(SettingsRepositoryContract::class);

        $correlationsData = $settingsCorrelationFactory->search($filters, 1, 50);

        return $correlationsData;
    }

    public function updateCorrelation(Request $request)
    {
        $correlationData = $request->get('correlations', []);
        $id = $request->get('id', []);

        $settingsRepo = pluginApp(SettingsRepositoryContract::class);

        $settingsRepo->update($correlationData, $id);
    }

    public function saveCorrelation(Request $request)
    {
        $data = $request->get('correlations', []);

        $settingsRepo = pluginApp(SettingsRepositoryContract::class);

        $response = $settingsRepo->create('PandaBlack', 'category', $data);

        return $response;
    }

    public function deleteAllCorrelations()
    {
        $settingsCorrelationFactory = pluginApp(SettingsRepositoryContract::class);

        $settingsCorrelationFactory->deleteAll('PandaBlack', 'category');

        $settingsCorrelationFactory->deleteAll('PandaBlack', 'attribute');
    }

    public function deleteCorrelation($id)
    {
        $settingsCorrelationFactory = pluginApp(SettingsRepositoryContract::class);

        $correlationDetails = $settingsCorrelationFactory->get($id);

        $attributesCollection = $correlationDetails->settings[1];

        foreach($attributesCollection as $attributeMapping) {
            $settingsCorrelationFactory->delete($attributeMapping->id);
        }

        $settingsCorrelationFactory->delete($id);
    }

    /** PandaBlack Categories */

    public function getPBCategories()
    {
        $app = pluginApp(AppController::class);

        $pbCategories = $app->authenticate('pandaBlack_categories');

        if(isset($pbCategories)) {
            $pbCategoryTree = [];
            foreach ($pbCategories as $key => $pbCategory) {
                if ($pbCategory['parent_id'] === null) {
                    $pbCategoryTree[] = [
                        'id' => (int)$key,
                        'name' => $pbCategory['name'],
                        'parentId' => 0,
                        'children' => $this->getPBChildCategories($pbCategories, (int)$key),
                    ];
                }
            }

            return json_encode($pbCategoryTree);
        }
    }

    private function getPBChildCategories($pbCategories, $parentId)
    {
        $pbChildCategoryTree = [];
        foreach ($pbCategories as $key => $pbCategory) {
            if ($pbCategory['parent_id'] === $parentId) {
                $pbChildCategoryTree[] = [
                    'id' => (int)$key,
                    'name' => $pbCategory['name'],
                    'children' => $this->getPBChildCategories($pbCategories, (int)$key)
                ];
            }
        }

        return $pbChildCategoryTree;
    }


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

                                    if($set) {
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
}
