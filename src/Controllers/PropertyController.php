<?php

namespace PandaBlack\Controllers;

use PandaBlack\Helpers\SettingsHelper;
use Plenty\Modules\Property\Contracts\PropertyRepositoryContract;
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
        $propertyRepo = pluginApp(PropertyRepositoryContract::class);
        $propertyId = $this->Settings->get('panda_black_category_as_property');

        if(!empty($propertyId)) {
             $propertyInfo = $propertyRepo->getProperty($propertyId, ['selections']);

             return $propertyInfo['selections'];
        }
    }
}