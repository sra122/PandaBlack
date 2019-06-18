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
use Plenty\Modules\Property\Contracts\PropertyRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertyRelationRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertyRelationValueRepositoryContract;
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
        $exportData = [];

        $filterVariations = ['updatedBetween', 'relatedUpdatedBetween'];

        foreach($filterVariations as $filterVariation)
        {
            $itemRepository = pluginApp(VariationSearchRepositoryContract::class);

            $itemRepository->setSearchParams([
                'with' => [
                    'item' => null,
                    'lang' => 'de',
                    'properties' => true,
                    'variationSalesPrices' => true,
                    'variationCategories' => true,
                    'variationClients' => true,
                    'variationAttributeValues' => true,
                    'variationSkus' => true,
                    'variationMarkets' => true,
                    'variationSuppliers' => true,
                    'variationWarehouses' => true,
                    'variationDefaultCategory' => true,
                    'variationBarcodes' => true,
                    'variationProperties' => true,
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
                'referrerId' => $this->Settings->get(SettingsHelper::ORDER_REFERRER),
                $filterVariation => time()-3600
            ]);

            $resultItems = $itemRepository->search();

            /*foreach($resultItems->getResult() as $variation) {
                array_push($exportData, $variation);
            }*/

            do {

                $manufacturerRepository = pluginApp(ManufacturerRepositoryContract::class);
                $variationStock = pluginApp(VariationStockRepositoryContract::class);
                $variationMarketIdentNumber = pluginApp(VariationMarketIdentNumberRepositoryContract::class);

                foreach($resultItems->getResult()  as $variation)
                {
                    $stockData = $variationStock->listStockByWarehouse($variation['id']);

                    $manufacturer = $manufacturerRepository->findById($variation['item']['manufacturerId'], ['*'])->toArray();

                    //ASIN
                    $asin = null;
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
                    $sku = null;
                    try {
                        if(count($variation['variationSkus']) > 0) {
                            foreach($variation['variationSkus'] as $skuInformation)
                            {
                                if($skuInformation->marketId === $this->Settings->get('orderReferrerId')) {
                                    $sku = $skuInformation->sku;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        $sku = null;
                    }

                    //EAN
                    $ean = [];

                    if(count($variation['variationBarcodes']) > 0) {
                        foreach($variation['variationBarcodes'] as $variationBarcode)
                        {
                            array_push($ean, $variationBarcode->code);
                        }
                    }

                    $textArray = $variation['item']->texts;
                    $variation['texts'] = $textArray->toArray();

                    $categoryId = $this->categoryIdFromSettingsRepo($variation['properties']);

                    $exportData[$variation['id']] = array(
                        'parent_product_id' => $variation['mainVariationId'],
                        'product_id' => $variation['id'],
                        'item_id' => $variation['itemId'],
                        'name' => $variation['item']['texts'][0]['name1'],
                        'price' => $variation['variationSalesPrices'][0]['price'],
                        'currency' => 'Euro',
                        'category' => $categoryId,
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
                        'sku' => $sku,
                        'ean' => (count($ean) > 0) ? implode(',', $ean) : null
                    );

                    /*$attributeSets = [];
                    foreach($variation['variationAttributeValues'] as $attribute) {

                        $attributeId = array_reverse(explode('-', $attribute['attribute']['backendName']))[0];
                        $attributeValue = array_reverse(explode('-', $attribute['attributeValue']['backendName']))[0];
                        $attributeSets[(int)$attributeId] = (int)$attributeValue;
                    }*/

                    $exportData[$variation['id']]['attributes'] = $this->attributesInfo($variation['properties'], $categoryId);
                }

            } while(!$resultItems->isLastPage());
        }

        $templateData = array(
            'exportData' => $exportData
        );
        return $templateData;
    }


    private function attributesInfo($properties, $categoryId)
    {
        $attributesInfo = [];

        $settingsHelper = pluginApp(SettingsHelper::class);
        $pbAttributes = $settingsHelper->get(SettingsHelper::ATTRIBUTES)[$categoryId];

        $pbMapping = $settingsHelper->get(SettingsHelper::MAPPING_INFO);

        $propertiesRepo = pluginApp(PropertyRepositoryContract::class);

        $propertyLists = $propertiesRepo->listProperties(1, 50, [], [], 0);

        $propertyInfos = [];

        foreach($propertyLists as $propertyList)
        {
            foreach($properties as $property)
            {
                if($property['propertyId'] == $propertyList['id'] && ($propertyList['id'] != $categoryId) && !empty($propertyList['selections'])) {

                    foreach($propertyList['selections'] as $selection)
                    {
                        if($selection['id'] == $property['relationValues'][0]['value']) {
                            $propertyInfos[$propertyList['id']] = $selection['relation']['relationValues'][0]['value'];
                        }
                    }
                }
            }
        }

        foreach($pbMapping['property'] as $key => $mappedProperty)
        {
            foreach($propertyInfos as $propertyInfo)
            {
                $attributesInfo[$key] = $propertyInfo;
                /*if($mappedProperty == $propertyInfo)
                {
                    foreach($pbAttributes as $pbAttribute)
                    {
                        if($pbAttribute['required'] && ($pbAttribute['name'] == $key))
                        {
                            foreach($pbAttribute['values'] as $value)
                            {
                                foreach($pbMapping['propertyValue'] as $propertyValue)
                                {
                                    if($propertyValue == $value) {
                                        $attributesInfo[$key] = $propertyValue;
                                    }
                                }
                            }
                        }
                    }
                    $attributesInfo[$key] = $propertyInfo;
                }*/
            }
        }

        $test = [
            'propertyInfos' => $propertyInfos,
            'pbMapping' => $pbMapping['property'],
            'propertiesList' => $propertyLists
        ];

        return $test;
    }

    /**
     * @return array
     */
    public function sendProductDetails()
    {
        $app = pluginApp(AppController::class);
        $productDetails = $this->productDetails();

        /*$productStatus = $this->productStatus($productDetails);

        if(!empty($productStatus['validProductDetails'])) {
            $validProductsWithSKU = $this->generateSKU($productStatus['validProductDetails']);
            $app->authenticate('products_to_pandaBlack', null, $validProductsWithSKU);
        }

        return $productStatus;*/

        return $productDetails;
    }


    private function productStatus($productDetails)
    {
        $app = pluginApp(AppController::class);

        $emptyAttributeProducts = [];
        $missingAttributeProducts = [];
        $wrongAttributeMapping = [];
        $noStockProducts = [];
        $noASINProducts = [];

        foreach($productDetails['exportData'] as $key => $productDetail)
        {
            $unfulfilledData = false;
            // Attributes Check
            if(empty($productDetail['attributes'])) {
                array_push($emptyAttributeProducts, $productDetail['product_id']);
                //unset($productDetails['exportData'][$key]);
                $unfulfilledData = true;
            } else {
                $attributes = $app->authenticate('pandaBlack_attributes', (int)$productDetail['category']);

                foreach($attributes as $attributeKey => $attribute) {
                    if(!array_key_exists($attributeKey, $productDetail['attributes']) && $attribute['required']) {
                        if(!in_array($productDetail['product_id'], $missingAttributeProducts)) {
                            $missingAttributeProducts[$productDetail['product_id']][$attributeKey] = $attribute['name'];
                            //array_push($missingAttributeProducts[$productDetail['product_id']][$attributeKey], $attribute['name']);
                            $unfulfilledData = true;
                            /*array_push($missingAttributeProducts, $productDetail['product_id']);
                            unset($productDetails['exportData'][$key]);
                            break;*/
                        }
                    }
                }
            }

            // Stock Check
            if(!isset($productDetail['quantity']) || $productDetail['quantity'] <= 0) {
                array_push($noStockProducts, $productDetail['product_id']);
                $unfulfilledData = true;
                //unset($productDetails['exportData'][$key]);
            }

            //ASIN Check
            if(!isset($productDetail['asin']) || empty($productDetail['asin'])) {
                array_push($noASINProducts, $productDetail['product_id']);
                $unfulfilledData = true;
                //unset($productDetails['exportData'][$key]);
            }

            if($unfulfilledData) {
                unset($productDetails['exportData'][$key]);
            }
        }

        $unfulfilledProducts = [
            'emptyAttributeProducts' => $emptyAttributeProducts,
            'missingAttributeProducts' => $missingAttributeProducts,
            'wrongAttributeMapping' => $wrongAttributeMapping,
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

            $stockUnits = $variationSKURepository->findByVariationId($validProduct['product_id']);

            if(count($stockUnits) <= 0) {

                $pbSkuExist = false;
                foreach($stockUnits as $stockUnit)
                {
                    if($stockUnit->marketId === $this->Settings->get('orderReferrerId')) {
                        $pbSkuExist = true;
                    }
                }

                if(!$pbSkuExist) {
                    $skuInfo = $variationSKURepository->create([
                        'variationId' => $validProduct['product_id'],
                        'marketId' => $this->Settings->get('orderReferrerId'),
                        'accountId' => 0,
                        'sku' => (string)$validProduct['product_id']
                    ])->toArray();

                    if(isset($validProduct['sku']) && !empty($validProduct['sku'])) {
                        $validProducts[$key]['sku'] = $skuInfo;
                    }
                }
            }
        }

        return $validProducts;
    }


    private function categoryIdFromSettingsRepo($properties)
    {
        $settingsHelper = pluginApp(SettingsHelper::class);
        $categoryPropertyId = $settingsHelper->get(SettingsHelper::CATEGORIES_AS_PROPERTIES);

        foreach($properties as $property)
        {
            if($property['propertyId'] == (int)$categoryPropertyId) {
                $categoriesList = $settingsHelper->get(SettingsHelper::CATEGORIES_LIST);

                $propertyRepo = pluginApp(PropertyRepositoryContract::class);
                $propertyLists = $propertyRepo->listProperties(1, 50, [], [], 0);

                foreach($propertyLists as $propertyList)
                {
                    if($propertyList['id'] == $property['propertyId'] && !empty($propertyList['selections'])) {

                        foreach($propertyList['selections'] as $selection)
                        {
                            if($selection['id'] == $property['relationValues'][0]['value']) {

                                return array_flip($categoriesList)[$selection['relation']['relationValues'][0]['value']];
                            }
                        }

                        return $propertyList['selections'];
                    }
                }
            }
        }
    }
}