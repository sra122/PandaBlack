<?php

namespace PandaBlack\Controllers;

use PandaBlack\Helpers\SettingsHelper;
use Plenty\Modules\Property\Contracts\PropertyRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertySelectionRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertyNameRepositoryContract;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;

Class PropertyController extends Controller
{
    /** @var SettingsHelper */
    protected $settings;

    public function __construct(SettingsHelper $SettingsHelper)
    {
        $this->settings = $SettingsHelper;
    }

    public function createCategoryAsProperty(Request $request)
    {
        $pbCategoryName = $request->get('categoryName');

        $propertyRepo = pluginApp(PropertyRepositoryContract::class);
        $propertySelectionRepo = pluginApp(PropertySelectionRepositoryContract::class);
        $propertyId = $this->getPBPropertyAsCategory();

        $pbCategoryExist = false;

        if(!empty($propertyId)) {
             $propertyInfo = $propertyRepo->getProperty($propertyId, ['selections'])->toArray();

             foreach($propertyInfo['selections'] as $selection) {
                 $selectionInfo = $propertySelectionRepo->getPropertySelection($selection['id'])->toArray();

                 if($selectionInfo['relation']['relationValues'][0]['value'] === $pbCategoryName) {
                     $pbCategoryExist = true;
                 }
             }

             $categoriesList = $this->settings->get(SettingsHelper::CATEGORIES_LIST);

             if(!empty($categoriesList) && in_array($pbCategoryName, $categoriesList)) {
                 if(!$pbCategoryExist) {
                     $selectionData = [
                         'propertyId' => $propertyId,
                         'relation' => [
                             [
                                 'relationValues' => [
                                     [
                                         'value' => $pbCategoryName,
                                         'lang' => 'de',
                                         'description' => ''
                                     ]
                                 ]
                             ]
                         ]
                     ];

                     $propertySelection = $propertySelectionRepo->createPropertySelection($selectionData);

                     if(!empty($propertySelection->id)) {
                         return $propertySelection->id;
                     }
                 }
             } else if(empty($categoriesList)) {
                 return false;
             }
        }
    }



    private function getPBPropertyAsCategory()
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