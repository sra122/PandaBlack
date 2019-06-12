<?php

namespace PandaBlack\Controllers;

use PandaBlack\Helpers\SettingsHelper;
use Plenty\Modules\Property\Contracts\PropertyRepositoryContract;
use Plenty\Modules\Property\Contracts\PropertySelectionRepositoryContract;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;

Class PropertyController extends Controller
{
    /** @var SettingsHelper */
    protected $Settings;

    public function __construct(SettingsHelper $SettingsHelper)
    {
        $this->Settings = $SettingsHelper;
    }

    public function createCategoryAsProperty(Request $request)
    {
        $pbCategoryName = $request->get('categoryName');

        $propertyRepo = pluginApp(PropertyRepositoryContract::class);
        $propertySelectionRepo = pluginApp(PropertySelectionRepositoryContract::class);
        $propertyId = $this->Settings->get('panda_black_category_as_property');

        $pbCategoryExist = false;

        if(!empty($propertyId)) {
             $propertyInfo = $propertyRepo->getProperty($propertyId, ['selections'])->toArray();

             foreach($propertyInfo['selections'] as $selection) {
                 $selectionInfo = $propertySelectionRepo->getPropertySelection($selection['id'])->toArray();

                 if($selectionInfo['relation']['relationValues'][0]['value'] === $pbCategoryName) {
                     $pbCategoryExist = true;
                 }
             }

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

                 return $propertySelection->id;
             }
        }
    }
}