<?php
/**
 * Created by PhpStorm.
 * User: sravan
 * Date: 13.06.19
 * Time: 12:07
 */

namespace PandaBlack\Controllers;


use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;

class MappingController extends Controller
{
    public function mapping(Request $request)
    {
        $mappingData = $request->get('mappingInformation');
        $categoryId = $request->get('categoryId');

        return $categoryId;
    }
}