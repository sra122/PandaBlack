<?php
namespace PandaBlack\Controllers;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Templates\Twig;
use Plenty\Modules\Plugin\DataBase\Contracts;
use Plenty\Modules\Item\Variation\Contracts\VariationSearchRepositoryContract;
use Plenty\Modules\Item\VariationCategory\Contracts\VariationCategoryRepositoryContract;
use Plenty\Modules\Item\VariationStock\Contracts\VariationStockRepositoryContract;
use Plenty\Modules\Item\Attribute\Contracts\AttributeRepositoryContract;
use Plenty\Modules\Item\Property\Contracts\PropertyRepositoryContract;
use Plenty\Modules\Item\Search\Mutators\KeyMutator;
use Plenty\Plugin\Application;
use Plenty\Modules\Item\Variation\Contracts\VariationRepositoryContract;
use Plenty\Modules\Category\Contracts\CategoryRepositoryContract;
use Plenty\Modules\System\Contracts\SystemInformationRepositoryContract;
use Plenty\Modules\System\Contracts\WebstoreRepositoryContract;
use Plenty\Modules\Market\Settings\Factories\SettingsCorrelationFactory;
use Plenty\Modules\Market\Settings\Contracts\SettingsRepositoryContract;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Item\Attribute\Contracts\AttributeValueRepositoryContract;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Modules\Item\ItemImage\Contracts\ItemImageRepositoryContract;
use Plenty\Modules\Item\VariationImage\Contracts\VariationImageRepositoryContract;
use Plenty\Modules\Order\Referrer\Contracts\OrderReferrerRepositoryContract;
use Plenty\Modules\Item\VariationWarehouse\Contracts\VariationWarehouseRepositoryContract;
use Plenty\Modules\Market\Helper\Contracts\MarketAttributeHelperRepositoryContract;
use Plenty\Modules\Item\Manufacturer\Contracts\ManufacturerRepositoryContract;
use Plenty\Plugin\Http\Request;
class ContentController extends Controller
{
    /**
     * @return array
     */
    public function productDetails()
    {
        $itemRepository = pluginApp(VariationSearchRepositoryContract::class);

        $itemRepository->setSearchParams([
            'with' => [
                'item' => null,
                'lang' => 'de',
                'variationSalesPrices' => true,
                'variationCategories' => true,
                'variationClients' => true,
                'VariationAttributeValues' => true,
                'variationSkus' => true,
                'variationMarkets' => true,
                'variationSuppliers' => true,
                'variationWarehouses' => true,
                'variationDefaultCategory' => true,
                'unit' => true,
                'variationStock' => [
                    'params' => [
                        'type' => 'virtual'
                    ],
                    'fields' => [
                        'stockNet'
                    ]
                ],
                'stock' => true,
                'images' => true,
            ]
        ]);

        $orderReferrerRepo = pluginApp(OrderReferrerRepositoryContract::class);
        $orderReferrerLists = $orderReferrerRepo->getList(['name', 'id']);

        $pandaBlackReferrerID = [];

        foreach($orderReferrerLists as $key => $orderReferrerList)
        {
            if(trim($orderReferrerList->name) === 'PandaBlack' && count($pandaBlackReferrerID) === 0) {
                array_push($pandaBlackReferrerID, $orderReferrerList);
            }
        }

        foreach($pandaBlackReferrerID as $pandaBlackId) {
            $itemRepository->setFilters([
                'referrerId' => (int)$pandaBlackId['id']
            ]);
        }


        $resultItems = $itemRepository->search();

        $items = [];
        $completeData = [];

        $settingsRepositoryContract = pluginApp(SettingsRepositoryContract::class);
        $categoryMapping = $settingsRepositoryContract->search(['marketplaceId' => 'PandaBlack', 'type' => 'category'], 1, 100)->toArray();

        $categoryId = [];

        foreach($categoryMapping['entries'] as $category) {
            $categoryId[$category->settings[0]['category'][0]['id']] = $category->settings;
        }

        $crons = $settingsRepositoryContract->search(['marketplaceId' => 'PandaBlack', 'type' => 'property'], 1, 100)->toArray();


        foreach($resultItems->getResult() as $key => $variation) {

            // Update only if products are updated in last 1 hour.
            if((time() - strtotime($variation['updatedAt'])) < 3600 || !isset($crons['entries']['pbItemCron'])) {

                if(!$variation['isMain'] && isset($categoryId[$variation['variationCategories'][0]['categoryId']])) {

                    $variationStock = pluginApp(VariationStockRepositoryContract::class);
                    $stockData = $variationStock->listStockByWarehouse($variation['id']);

                    $manufacturerRepository = pluginApp(ManufacturerRepositoryContract::class);
                    $manufacturer = $manufacturerRepository->findById($variation['item']['manufacturerId'], ['*'])->toArray();

                    $textArray = $variation['item']->texts;
                    $variation['texts'] = $textArray->toArray();

                    $categoryMappingInfo = $categoryId[$variation['variationCategories'][0]['categoryId']];
                    $items[$key] = [$variation, $categoryId[$variation['variationCategories'][0]['categoryId']], $manufacturer];

                    $completeData[$key] = array(
                        'parent_product_id' => $variation['mainVariationId'],
                        'product_id' => $variation['id'],
                        'item_id' => $variation['itemId'],
                        'name' => $variation['item']['texts'][0]['name1'],
                        'price' => $variation['variationSalesPrices'][0]['price'],
                        'currency' => $variation['variationSalesPrices'][0]['price'],
                        'category' => $categoryMappingInfo[0]['vendorCategory'][0]['name'],
                        'short_description' => $variation['item']['texts'][0]['description'],
                        'image_url' => $variation['images'][0]['url'],
                        'color' => '',
                        'size' => '',
                        'content_supplier' => $manufacturer['name'],
                        'product_type' => '',
                        'quantity' => $stockData,
                        'store_name' => '',
                        'status' => '',
                        'brand' => '',
                        'variant_attribute_1' => '',
                        'last_update_at' => $variation['updatedAt'],
                    );
                }
            }
        }

        $templateData = array(
            'exportData' => $completeData,
            'completeData' => $items
        );
        return $templateData;
    }


    /**
     * @param SettingsRepositoryContract $settingRepo
     * @param LibraryCallContract $libCall
     * @return mixed
     */
    public function sendProductDetails()
    {
        $settingRepo = pluginApp(SettingsRepositoryContract::class);
        $libCall = pluginApp(LibraryCallContract::class);


        $properties = $settingRepo->find('PandaBlack', 'property');

        foreach($properties as $key => $property) {

            $productDetails = $this->productDetails();

            if(!empty($productDetails['exportData'])) {

                if(isset($property->settings['Token']) && ($property->settings['Token']['expires_in'] > time())) {

                    $this->saveCronTime();

                    $response = $libCall->call(
                        'PandaBlack::products_to_pandablack',
                        [
                            'token' => $property->settings['Token']['token'],
                            'product_details' => $productDetails
                        ]
                    );
                    return $response;
                } else if(isset($property->settings['Token']) && ($property->settings['Token']['refresh_token_expires_in'] > time())) {

                    $this->saveCronTime();

                    $response = $libCall->call(
                        'PandaBlack::products_to_pandablack',
                        [
                            'token' => $property->settings['Token']['refresh_token'],
                            'product_details' => $productDetails
                        ]
                    );
                    return $response;
                }
            }
        }
    }

    /**
     * @return mixed
     */
    public function saveCronTime()
    {
        $settingRepo = pluginApp(SettingsRepositoryContract::class);

        $crons = $settingRepo->search(['marketplaceId' => 'PandaBlack', 'type' => 'property'], 1, 100)->toArray();

        foreach($crons as $key => $cron) {
            if(isset($crons['entries']['pbItemCron'])) {
                $cronData = [
                    'pbItemCron' => [
                        'pastCronTime' => $crons['entries']['pbItemCron']['presentCronTime'],
                        'presentCronTime' => time()
                    ]
                ];
                $response = $settingRepo->update($cronData, $key);
                return $response;
            }
        }

        $cronData = [
            'pbItemCron' => [
                'pastCronTime' => null,
                'presentCronTime' => time()
            ]
        ];

        $response = $settingRepo->create('PandaBlack', 'property', $cronData);

        return $response;
    }
}