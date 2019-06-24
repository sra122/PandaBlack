<?php
namespace PandaBlack\Controllers;

use PandaBlack\Helpers\PBApiHelper;
use PandaBlack\Helpers\SettingsHelper;
use Plenty\Plugin\Controller;
use Plenty\Modules\Item\Variation\Contracts\VariationSearchRepositoryContract;
use Plenty\Modules\Item\VariationStock\Contracts\VariationStockRepositoryContract;
use Plenty\Modules\Item\Manufacturer\Contracts\ManufacturerRepositoryContract;
use Plenty\Modules\Item\VariationMarketIdentNumber\Contracts\VariationMarketIdentNumberRepositoryContract;
use Plenty\Modules\Item\VariationSku\Contracts\VariationSkuRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertyRepositoryContract;
class ContentController extends Controller
{
    /** @var SettingsHelper */
    protected $settingsHelper;

    protected $exportData = [];

    public function __construct(SettingsHelper $settingsHelper)
    {
        $this->settingsHelper = $settingsHelper;
    }

    /**
     * Exporting the Products information
     * @return array
     */
    public function exportingProducts()
    {
        $filterVariations = ['updatedBetween', 'relatedUpdatedBetween'];

        foreach($filterVariations as $filterVariation)
        {
            $this->collectProductInfo($filterVariation);
        }

        $templateData = array(
            'exportData' => $this->exportData
        );
        return $templateData;
    }

    /** Collecting the Product Information
     * @param $filterVariation
     */
    public function collectProductInfo($filterVariation = null)
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
            'referrerId' => $this->settingsHelper->get(SettingsHelper::ORDER_REFERRER),
        ]);

        if(!empty($filterVariation)) {
            $itemRepository->setFilters([
                $filterVariation => time()-3600
            ]);
        }

        $resultItems = $itemRepository->search();

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
                            if($skuInformation->marketId === $this->settingsHelper->get('orderReferrerId')) {
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

                $this->exportData[$variation['id']] = array(
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

                $this->exportData[$variation['id']]['attributes'] = $this->attributesInfo($variation['properties'], $categoryId);
            }

        } while(!$resultItems->isLastPage());
    }

    /**
     * @param $properties
     * @param $categoryId
     * @return array
     */
    private function attributesInfo($properties, $categoryId)
    {
        $attributeDetails = [];

        $pbAttributes = $this->settingsHelper->get(SettingsHelper::ATTRIBUTES)[$categoryId];

        // In case, if attributes are not saved in Settings.
        if(empty($pbAttributes)) {
            $pbApiHelper = pluginApp(PBApiHelper::class);
            $attributes = $this->settingsHelper->get(SettingsHelper::ATTRIBUTES);

            $attributes[$categoryId] = $pbApiHelper->fetchPBAttributes($categoryId);

            $this->settingsHelper->set(SettingsHelper::ATTRIBUTES, $attributes);

            $pbAttributes = $attributes[$categoryId];
        }

        $pbMapping = $this->settingsHelper->get(SettingsHelper::MAPPING_INFO);

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
            foreach($propertyInfos as $id => $propertyInfo)
            {
                foreach($propertyLists as $propertyList)
                {
                    if($propertyList['id'] == $id && ($this->getPropertyNameInDE($propertyList['names']) == $key))
                    {
                        foreach($pbMapping['propertyValue'] as $propertyValueKey => $propertyValue)
                        {
                            if($propertyInfo == $propertyValue)
                            {
                                $attributeDetails[$key] = $propertyValueKey;
                            }
                        }
                    }
                }
            }
        }

        // Check the Attributes that are mapped are present in PB attributes list of the selected Category.
        if(!empty($attributeDetails)) {
            foreach($attributeDetails as $attributeName => $attributeDetail)
            {
                $matched = false;
                foreach($pbAttributes as $pbAttribute)
                {
                    if($pbAttribute['required'] && ($pbAttribute['name'] == $attributeName))
                    {
                        foreach($pbAttribute['values'] as $pbAttributeValue)
                        {
                            if($pbAttributeValue == $attributeDetail) {
                                $matched = true;
                            }
                        }
                    }
                }

                if(!$matched) {
                    unset($attributeDetails[$attributeName]);
                }
            }
        }

        return $attributeDetails;
    }


    public function getPropertyNameInDE($names)
    {
        $propertyName = '';

        foreach($names as $name)
        {
            if($name['lang'] == 'de') {
                $propertyName = $name['name'];
            }
        }

        return $propertyName;
    }

    /**
     * @return array
     */
    public function sendProductDetails()
    {
        $app = pluginApp(AppController::class);
        $productDetails = $this->exportingProducts();

        $productStatus = $this->productStatus($productDetails);

        if(!empty($productStatus['validProductDetails'])) {
            $validProductsWithSKU = $this->generateSKU($productStatus['validProductDetails']);
            $app->authenticate('products_to_pandaBlack', null, $validProductsWithSKU);
        }

        $adminNotification = $this->settingsHelper->get(SettingsHelper::NOTIFICATION)['admin'];

        /* Appending the Admin Notification */
        if(empty($adminNotification)) {
            $productStatus['unfulfilledProducts']['admin'] = [];
        } else {
            $productStatus['unfulfilledProducts']['admin'] = $adminNotification;
        }

        $this->settingsHelper->set(SettingsHelper::NOTIFICATION, $productStatus['unfulfilledProducts']);

        return $productStatus;
    }

    private function productStatus($productDetails)
    {
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
                $unfulfilledData = true;
            } else {
                $attributes = $this->settingsHelper->get(SettingsHelper::ATTRIBUTES)[(int)$productDetail['category']];

                $count = 0;
                foreach($attributes as $attributeKey => $attribute) {
                    if(!array_key_exists($attribute['name'], $productDetail['attributes']) && $attribute['required'] && ($attribute['values'] !== null)) {
                        if(!in_array($productDetail['product_id'], $missingAttributeProducts)) {
                            $missingAttributeProducts[$productDetail['product_id']][$count++] = $attribute['name'];
                            $unfulfilledData = true;
                        }
                    }
                }
            }

            // Stock Check
            if(!isset($productDetail['quantity']) || $productDetail['quantity'] <= 0) {
                array_push($noStockProducts, $productDetail['product_id']);
                $unfulfilledData = true;
            }

            //ASIN Check
            if(!isset($productDetail['asin']) || empty($productDetail['asin'])) {
                array_push($noASINProducts, $productDetail['product_id']);
                $unfulfilledData = true;
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
                    if($stockUnit->marketId === $this->settingsHelper->get('orderReferrerId')) {
                        $pbSkuExist = true;
                    }
                }

                if(!$pbSkuExist) {
                    $skuInfo = $variationSKURepository->create([
                        'variationId' => $validProduct['product_id'],
                        'marketId' => $this->settingsHelper->get('orderReferrerId'),
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
        $categoryPropertyId = $this->settingsHelper->get(SettingsHelper::CATEGORIES_AS_PROPERTIES);

        foreach($properties as $property)
        {
            if($property['propertyId'] == (int)$categoryPropertyId) {
                $categoriesList = $this->settingsHelper->get(SettingsHelper::CATEGORIES_LIST);

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


    public function validateProducts()
    {
        $this->collectProductInfo();

        $productStatus = $this->productStatus(array('exportData' => $this->exportData));

        $adminNotification = $this->settingsHelper->get(SettingsHelper::NOTIFICATION)['admin'];

        /* Appending the Admin Notification */
        if(empty($adminNotification)) {
            $productStatus['unfulfilledProducts']['admin'] = [];
        } else {
            $productStatus['unfulfilledProducts']['admin'] = $adminNotification;
        }

        $this->settingsHelper->set(SettingsHelper::NOTIFICATION, $productStatus['unfulfilledProducts']);

        return $this->exportData;
    }
}