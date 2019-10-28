<?php
namespace PandaBlack\Controllers;
use PandaBlack\Helpers\PBApiHelper;
use PandaBlack\Helpers\SettingsHelper;
use Plenty\Modules\Item\ItemImage\Contracts\ItemImageRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertyNameRepositoryContract;
use Plenty\Plugin\Controller;
use Plenty\Modules\Item\Variation\Contracts\VariationSearchRepositoryContract;
use Plenty\Modules\Item\VariationStock\Contracts\VariationStockRepositoryContract;
use Plenty\Modules\Item\Manufacturer\Contracts\ManufacturerRepositoryContract;
use Plenty\Modules\Item\VariationMarketIdentNumber\Contracts\VariationMarketIdentNumberRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertyRepositoryContract;
class ContentController extends Controller
{
    /** @var SettingsHelper */
    protected $settings;
    public $exportData = [];
    public $variationItems = [];
    public $attributes;
    public $categories;
    public function __construct(SettingsHelper $SettingsHelper)
    {
        $this->settings = $SettingsHelper;
        $this->attributes = pluginApp(AttributeController::class);
        $this->categories = pluginApp(CategoryController::class);
    }

    /**
     * @param null $filterVariation
     * @param int $hours
     */
    private function productsExtraction($filterVariation = null, $hours = 1)
    {
        $marketId = $this->settings->get('orderReferrerId');

        if(empty($marketId)) {
            $this->settings->getReferrerId();
        }

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
            'referrerId' => (int)$this->settings->get(SettingsHelper::ORDER_REFERRER),
            $filterVariation => time()-(3600*$hours)
        ]);

        $resultItems = $itemRepository->search();

        do {
            $manufacturerRepository = pluginApp(ManufacturerRepositoryContract::class);
            $variationStock = pluginApp(VariationStockRepositoryContract::class);
            $variationMarketIdentNumber = pluginApp(VariationMarketIdentNumberRepositoryContract::class);
            foreach($resultItems->getResult()  as $variation)
            {
                if(!isset($this->variationItems[$variation['id']])) {
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
                    //Images
                    $images = pluginApp(ItemImageRepositoryContract::class);
                    $imageDetails = $images->findByItemId($variation['itemId']);
                    $imageUrls = [];
                    foreach($imageDetails as $imageDetail)
                    {
                        array_push($imageUrls, $imageDetail['url']);
                    }

                    //SKU
                    $sku = null;
                    if(count($variation['variationSkus']) > 0) {
                        foreach($variation['variationSkus'] as $skuInformation)
                        {
                            if($skuInformation['marketId'] === $this->settings->get(SettingsHelper::ORDER_REFERRER)) {
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
                        'images' => $imageUrls,
                        'image_url' => $imageUrls[0],
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
                        'sku' => (string)$variation['id'],
                        'ean' => (count($ean) > 0) ? implode(',', $ean) : null
                    );
                    $this->exportData[$variation['id']]['attributes'] = $this->attributesInfo($variation['properties'], $categoryId);
                    $this->variationItems[$variation['id']] = $variation['id'];
                }

            }
        } while(!$resultItems->isLastPage());

        return $this->exportData;
    }

    /**
     * @param int $hours
     * @return array
     */
    public function productDetails($hours = 1)
    {

        $filterVariations = ['updatedBetween', 'relatedUpdatedBetween'];
        foreach($filterVariations as $filterVariation)
        {
            $this->productsExtraction($filterVariation, $hours);
        }
        $templateData = array(
            'exportData' => $this->exportData
        );
        return $templateData;
    }


    private function attributesInfo($properties, $categoryId)
    {
        $attributeDetails = [];
        $attributes = pluginApp(AttributeController::class);
        $pbAttributes = $attributes->getPBAttributes($categoryId);

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
                                $attributeDetails[$key] = (string)$propertyValueKey;
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


    public function sendProductDetails($hours = 24)
    {
        $app = pluginApp(AppController::class);
        $productDetails = $this->productDetails($hours);
        $app->authenticate('products_to_pandaBlack', null, $productDetails);
        return $productDetails;
    }

    private function categoryIdFromSettingsRepo($properties)
    {
        $categoryPropertyId = $this->categoriesAsProperties();
        foreach($properties as $property)
        {
            if($property['propertyId'] == (int)$categoryPropertyId) {
                $categoriesList = $this->categories->getCategoriesList();
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


    /**
     * @return |null
     */
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
}