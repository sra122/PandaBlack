<?php

namespace PandaBlack\Controllers;

use PandaBlack\Helpers\SettingsHelper;
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
        $propertySelectionRepo = pluginApp(PropertySelectionRepositoryContract::class);
        $propertyId = $this->Settings->get('panda_black_category_as_property');

        return $propertyId;

        /*if(!empty($propertyId)) {
             $categoryName = $request->get('categoryName');
             $propertySelectionRepo->listPropertySelections(1, 50);

             return $propertySelectionRepo;
        }*/
    }
}