<?php
namespace PandaBlack\Controllers;

use PandaBlack\Helpers\SettingsHelper;
use Plenty\Plugin\Controller;
use Plenty\Modules\Item\Variation\Contracts\VariationSearchRepositoryContract;
use Plenty\Modules\Item\VariationStock\Contracts\VariationStockRepositoryContract;
use Plenty\Modules\Market\Settings\Contracts\SettingsRepositoryContract;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Modules\Order\Referrer\Contracts\OrderReferrerRepositoryContract;
use Plenty\Modules\Item\Manufacturer\Contracts\ManufacturerRepositoryContract;
use Plenty\Modules\Item\VariationImage\Contracts\VariationImageRepositoryContract;
use Plenty\Modules\Item\VariationMarketIdentNumber\Contracts\VariationMarketIdentNumberRepositoryContract;
use Plenty\Plugin\Http\Request;
class ContentController extends Controller
{
    /** @var SettingsHelper */
    protected $Settings;

    public function __construct(SettingsHelper $SettingsHelper)
    {
        $this->Settings = $SettingsHelper;
    }

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
                'variationAttributeValues' => true,
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

        return [1];

        $itemRepository->setFilters([
            'referrerId' => $this->Settings->get('orderReferrerId')
        ]);


        $resultItems = $itemRepository->search();

        $items = [];
        $exportData = [];

        $settingsRepositoryContract = pluginApp(SettingsRepositoryContract::class);
        $categoryMapping = $settingsRepositoryContract->search(['marketplaceId' => 'PandaBlack', 'type' => 'category'], 1, 100)->toArray();

        $categoryId = [];

        foreach($categoryMapping['entries'] as $category) {
            $categoryId[$category->settings[0]['category'][0]['id']] = $category->settings;
        }

        foreach($resultItems as $key => $variation) {

            // Update only if products are updated in last 1 hour.
            if((time() - strtotime($variation['updatedAt'])) < 3600 && isset($categoryId[$variation['variationCategories'][0]['categoryId']])) {

                if(isset($categoryId[$variation['variationCategories'][0]['categoryId']])) {

                    $variationStock = pluginApp(VariationStockRepositoryContract::class);
                    $stockData = $variationStock->listStockByWarehouse($variation['id']);

                    $manufacturerRepository = pluginApp(ManufacturerRepositoryContract::class);
                    $manufacturer = $manufacturerRepository->findById($variation['item']['manufacturerId'], ['*'])->toArray();

                    $variationMarketIdentNumber = pluginApp(VariationMarketIdentNumberRepositoryContract::class);
                    $asin = $variationMarketIdentNumber->findByVariationId($variation['id']);

                    $textArray = $variation['item']->texts;
                    $variation['texts'] = $textArray->toArray();

                    $categoryMappingInfo = $categoryId[$variation['variationCategories'][0]['categoryId']];
                    $items[$key] = [$variation, $categoryId[$variation['variationCategories'][0]['categoryId']], $manufacturer];

                    $exportData[$key] = array(
                        'parent_product_id' => $variation['mainVariationId'],
                        'product_id' => $variation['id'],
                        'item_id' => $variation['itemId'],
                        'name' => $variation['item']['texts'][0]['name1'],
                        'price' => $variation['variationSalesPrices'][0]['price'],
                        'currency' => 'Euro',
                        'category' => $categoryMappingInfo[0]['vendorCategory'][0]['id'],
                        'short_description' => $variation['item']['texts'][0]['description'],
                        'image_url' => $variation['images'][0]['url'],
                        'color' => '',
                        'size' => '',
                        'content_supplier' => $manufacturer['name'],
                        'product_type' => '',
                        'quantity' => $stockData[0]['netStock'],
                        'store_name' => '',
                        'status' => $variation['isActive'],
                        'brand' => $manufacturer['name'],
                        'last_update_at' => $variation['updatedAt'],
                        'asin' => $asin
                    );

                    $attributeSets = [];
                    foreach($variation['variationAttributeValues'] as $attribute) {

                        $attributeId = array_reverse(explode('-', $attribute['attribute']['backendName']))[0];
                        $attributeValue = array_reverse(explode('-', $attribute['attributeValue']['backendName']))[0];
                        $attributeSets[(int)$attributeId] = (int)$attributeValue;
                    }

                    $exportData[$key]['attributes'] = $attributeSets;
                }
            }
        }

        $templateData = array(
            'exportData' => $exportData
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
        $app = pluginApp(AppController::class);
        $productDetails = $this->productDetails();

        if(!empty($productDetails['exportData'])) {
            $app->authenticate('products_to_pandaBlack', null, $productDetails);
        }
    }
}