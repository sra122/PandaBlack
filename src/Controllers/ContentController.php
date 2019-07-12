<?php
namespace PandaBlack\Controllers;
use PandaBlack\Helpers\PBApiHelper;
use PandaBlack\Helpers\SettingsHelper;
use Plenty\Modules\Property\Contracts\PropertyNameRepositoryContract;
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
    protected $settings;
    public $exportData = [];
    public function __construct(SettingsHelper $SettingsHelper)
    {
        $this->settings = $SettingsHelper;
    }

    private function productsExtraction($filterVariation = null, $hours = 1)
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
            'referrerId' => $this->settings->get(SettingsHelper::ORDER_REFERRER),
            $filterVariation => time()-(3600*$hours)
        ]);

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
                if(count($variation['variationSkus']) > 0) {
                    foreach($variation['variationSkus'] as $skuInformation)
                    {
                        if($skuInformation['marketId'] === $this->settings->get('orderReferrerId')) {
                            $sku = $skuInformation['sku'];
                        }
                    }
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
                    'sku' => (count($variation['variationSkus']) > 0) ? $this->getSKUDetails($variation['variationSkus']) : null,
                    'ean' => (count($ean) > 0) ? implode(',', $ean) : null
                );
                $this->exportData[$variation['id']]['attributes'] = $this->attributesInfo($variation['properties'], $categoryId);
            }
        } while(!$resultItems->isLastPage());
    }

    /**
     * @return array
     */
    public function productDetails()
    {
        $filterVariations = ['updatedBetween', 'relatedUpdatedBetween'];
        foreach($filterVariations as $filterVariation)
        {
            $this->productsExtraction($filterVariation);
        }
        $templateData = array(
            'exportData' => $this->exportData
        );
        return $templateData;
    }


    private function getSKUDetails($variationSkus)
    {
        foreach($variationSkus as $skuInfo)
        {
            if((int)$skuInfo['marketId'] == (int)$this->settings->get('orderReferrerId')) {
                return $skuInfo['sku'];
            }
        }
    }


    private function attributesInfo($properties, $categoryId)
    {
        $attributeDetails = [];
        $pbAttributes = $this->settings->get(SettingsHelper::ATTRIBUTES)[$categoryId];

        // In case, if attributes are not saved in Settings.
        if(empty($pbAttributes)) {
            $pbApiHelper = pluginApp(PBApiHelper::class);
            $attributes = $this->settings->get(SettingsHelper::ATTRIBUTES);
            $attributes[$categoryId] = $pbApiHelper->fetchPBAttributes($categoryId);
            if(!empty($attributes[$categoryId])) {
                $this->settings->set(SettingsHelper::ATTRIBUTES, $attributes);
                $pbAttributes = $attributes[$categoryId];
            }
        }

        $pbMapping = $this->settings->get(SettingsHelper::MAPPING_INFO);
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
                foreach($pbAttributes as $key => $pbAttribute)
                {
                    if($pbAttribute['required'])
                    {
                        foreach($pbAttribute['values'] as $pbAttributeValue)
                        {
                            if($pbAttributeValue === $attributeDetail) {
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



    public function sendProductDetails()
    {
        $app = pluginApp(AppController::class);
        $mapping = pluginApp(MappingController::class);
        $productDetails = $this->productDetails();
        $productStatus = $this->productStatus($productDetails);

        if(!empty($productStatus['validProductDetails'])) {
            $validProductsWithSKU = $this->generateSKU($productStatus['validProductDetails']);
            $app->authenticate('products_to_pandaBlack', null, $validProductsWithSKU);
        }
        $productStatus['unfulfilledProducts']['admin'] = $mapping->updateNotifications()['admin'];
        $this->settings->set(SettingsHelper::NOTIFICATION, $productStatus['unfulfilledProducts']);
    }

    private function productStatus($productDetails)
    {
        $emptyAttributeProducts = [];
        $missingAttributeProducts = [];
        $wrongAttributeMapping = [];
        $noStockProducts = [];
        $noASINProducts = [];
        $errorProducts = [];
        foreach($productDetails['exportData'] as $key => $productDetail)
        {
            $unfulfilledData = false;
            // Attributes Check
            if(empty($productDetail['attributes'])) {
                array_push($emptyAttributeProducts, $productDetail['product_id']);
                $unfulfilledData = true;
                if(empty($errorProducts[$productDetail['product_id']])) {
                    $errorProducts[$productDetail['product_id']] = ['emptyAttributeProduct'];
                } else {
                    $errorProducts[$productDetail['product_id']] = array_merge($errorProducts[$productDetail['product_id']], ['emptyAttributeProduct']);
                }

            } else {
                $attributes = $this->settings->get(SettingsHelper::ATTRIBUTES)[(int)$productDetail['category']];
                $count = 0;
                foreach($attributes as $attributeKey => $attribute) {
                    if(!array_key_exists($attribute['name'], $productDetail['attributes']) && $attribute['required']) {
                        if(!in_array($productDetail['product_id'], $missingAttributeProducts)) {
                            $missingAttributeProducts[$productDetail['product_id']][$count++] = $attribute['name'];
                            $unfulfilledData = true;
                        }
                    }
                }
                if(isset($missingAttributeProducts[$productDetail['product_id']])) {
                    if(empty($errorProducts[$productDetail['product_id']])) {
                        $errorProducts[$productDetail['product_id']] = $missingAttributeProducts[$productDetail['product_id']];
                    } else {
                        $errorProducts[$productDetail['product_id']] = array_merge($errorProducts[$productDetail['product_id']], $missingAttributeProducts[$productDetail['product_id']]);
                    }
                }

            }
            // Stock Check
            if(!isset($productDetail['quantity']) || $productDetail['quantity'] <= 0) {
                array_push($noStockProducts, $productDetail['product_id']);
                $unfulfilledData = true;
                if(empty($errorProducts[$productDetail['product_id']])) {
                    $errorProducts[$productDetail['product_id']] = ['No-Stock'];
                } else {
                    $errorProducts[$productDetail['product_id']] = array_merge($errorProducts[$productDetail['product_id']], ['No-Stock']);
                }
            }
            //ASIN Check
            if(!isset($productDetail['asin']) || empty($productDetail['asin'])) {
                array_push($noASINProducts, $productDetail['product_id']);
                $unfulfilledData = true;
                if(empty($errorProducts[$productDetail['product_id']])) {
                    $errorProducts[$productDetail['product_id']] = ['No-Asin'];
                } else {
                    $errorProducts[$productDetail['product_id']] = array_merge($errorProducts[$productDetail['product_id']], ['No-Asin']);
                }
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
            'noAsinProducts' => $noASINProducts,
            'errorProducts' => $errorProducts
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
            if(empty($validProduct['sku'])) {
                $variationSKURepository = pluginApp(VariationSkuRepositoryContract::class);
                $stockUnits = $variationSKURepository->findByVariationId($validProduct['product_id']);

                if(count($stockUnits) <= 0) {
                    $skuInfo = $variationSKURepository->create([
                        'variationId' => $validProduct['product_id'],
                        'marketId' => $this->settings->get('orderReferrerId'),
                        'accountId' => 0,
                        'sku' => (string)$validProduct['product_id']
                    ])->toArray();
                    if(isset($validProduct['sku'])) {
                        $validProducts[$key]['sku'] = $skuInfo;
                    }
                }
            }
        }
        return $validProducts;
    }


    private function categoryIdFromSettingsRepo($properties)
    {
        $categoryPropertyId = $this->categoriesAsProperties();
        foreach($properties as $property)
        {
            if($property['propertyId'] == (int)$categoryPropertyId) {
                $categoriesList = $this->settings->get(SettingsHelper::CATEGORIES_LIST);
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

        return $categoryPropertyId;
    }


    private function categoriesAsProperties()
    {
        if(empty($this->settings->get(SettingsHelper::CATEGORIES_AS_PROPERTIES))) {

            $propertyNameRepository = pluginApp(PropertyNameRepositoryContract::class);

            $properties = $propertyNameRepository->listNames();

            foreach($properties as $property)
            {
                if($property->name === SettingsHelper::PB_KATEGORIE_PROPERTY) {
                    $this->settings->set(SettingsHelper::CATEGORIES_AS_PROPERTIES, $property->propertyId);
                }
            }
        }

        return $this->settings->get(SettingsHelper::CATEGORIES_AS_PROPERTIES);
    }


    public function saveProperty()
    {
        $app = pluginApp(AppController::class);
        return $app->authenticate('pandaBlack_categories');
    }
}