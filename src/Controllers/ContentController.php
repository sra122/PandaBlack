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
use Plenty\Modules\Item\VariationSku\Contracts\VariationSkuRepositoryContract;
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


        $manufacturerRepository = pluginApp(ManufacturerRepositoryContract::class);
        $variationStock = pluginApp(VariationStockRepositoryContract::class);
        $variationMarketIdentNumber = pluginApp(VariationMarketIdentNumberRepositoryContract::class);
        $variationSKURepository = pluginApp(VariationSkuRepositoryContract::class);

        foreach($resultItems->getResult()  as $key => $variation) {

            // Update only if products are updated in last 1 hour.
            if((time() - strtotime($variation['relatedUpdatedAt'])) < 3600 && isset($categoryId[$variation['variationCategories'][0]['categoryId']])) {

                if(isset($categoryId[$variation['variationCategories'][0]['categoryId']])) {

                    $stockData = $variationStock->listStockByWarehouse($variation['id']);

                    $manufacturer = $manufacturerRepository->findById($variation['item']['manufacturerId'], ['*'])->toArray();

                    //ASIN
                    try {
                        $identNumbers = $variationMarketIdentNumber->findByVariationId($variation['id']);

                        foreach($identNumbers as $identNumber)
                        {
                            if($identNumber['type'] === 'ASIN' && $identNumber['variationId'] === $variation['id']) {
                                $asin = $identNumber['value'];
                            }
                        }
                    } catch (\Exception $e) {
                        $asin = null;
                    }


                    //SKU
                    try {
                        $sku = $variationSKURepository->findByVariationId($variation['id']);
                    } catch (\Exception $e) {
                        $sku = null;
                    }

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
                        'last_update_at' => $variation['relatedUpdatedAt'],
                        'asin' => $asin,
                        'sku' => $sku
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

        $productStatus = $this->productStatus($productDetails);
        $validProductsWithSKU = $this->generateSKU($productStatus['validProductDetails']);

        /*if(!empty($productStatus['validProductDetails'])) {
            $validProductsWithSKU = $this->generateSKU($productStatus['validProductDetails']);
            $app->authenticate('products_to_pandaBlack', null, $validProductsWithSKU);
        }*/

        return $validProductsWithSKU;
    }


    private function productStatus($productDetails)
    {
        $app = pluginApp(AppController::class);

        $emptyAttributeProducts = [];
        $missingAttributeProducts = [];
        $noStockProducts = [];
        $noASINProducts = [];

        foreach($productDetails['exportData'] as $key => $productDetail)
        {
            // Attributes Check
            if(empty($productDetail['attributes'])) {
                array_push($emptyAttributeProducts, $productDetail['product_id']);
                unset($productDetails['exportData'][$key]);
            } else {
                $attributes = $app->authenticate('pandaBlack_attributes', (int)$productDetail['category']);

                foreach($attributes as $attributeKey => $attribute) {
                    if(!array_key_exists($attributeKey, $productDetail['attributes']) && $attribute['required']) {
                        if(!in_array($productDetail['product_id'], $missingAttributeProducts)) {
                            array_push($missingAttributeProducts, $productDetail['product_id']);
                            unset($productDetails['exportData'][$key]);
                            break;
                        }
                    }
                }
            }


            // Stock Check
            if(!isset($productDetail['quantity']) || $productDetail['quantity'] <= 0) {
                array_push($noStockProducts, $productDetail['product_id']);
                unset($productDetails['exportData'][$key]);
            }


            //ASIN Check
            if(!isset($productDetail['asin']) || empty($productDetail['asin'])) {
                array_push($noASINProducts, $productDetail['product_id']);
                unset($productDetails['exportData'][$key]);
            }
        }

        $unfulfilledProducts = [
            'emptyAttributeProducts' => $emptyAttributeProducts,
            'missingAttributeProducts' => $missingAttributeProducts,
            'noStockProducts' => $noStockProducts,
            'noAsinProducts' => $noASINProducts
        ];

        $productStatus = [
            'validProductDetails' => $productDetails['exportData'],
            'unfulfilledProducts' => $unfulfilledProducts
        ];

        return $productStatus;
    }


    private function generateSKU($validProducts)
    {
        foreach($validProducts as $key => $validProduct)
        {
            $variationSKURepository = pluginApp(VariationSkuRepositoryContract::class);
            $skuRepo = $variationSKURepository->create([
                    'variationId' => $validProduct['product_id'],
                    'marketId' => 0,
                    'accountId' => 0,
                    'sku' => (isset($validProduct['sku']) && !empty($validProduct['sku'])) ? $validProduct['sku'] : ''
                ]);

            if(isset($validProduct['sku']) && !empty($validProduct['sku'])) {
                $validProducts[$key]['sku'] = $skuRepo->toArray()['sku'];
            }
        }

        return $validProducts;

    }
}