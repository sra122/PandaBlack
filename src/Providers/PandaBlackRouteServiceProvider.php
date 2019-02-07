<?php

namespace PandaBlack\Providers;

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\Router;
use Plenty\Plugin\Routing\ApiRouter;

class PandaBlackRouteServiceProvider extends RouteServiceProvider
{
    /**
     * @param Router $router
     * @param ApiRouter $api
     */
    public function map(Router $router, ApiRouter $api)
    {
        //Authentication route
        $router->get('markets/panda-black/auth/authentication', 'PandaBlack\Controllers\AuthController@getAuthentication');

        $api->version(['v1'], ['middleware' => ['oauth']], function ($router) {
            $router->get('markets/panda-black/login-url', 'PandaBlack\Controllers\AuthController@getLoginUrl');
            $router->post('markets/panda-black/session', 'PandaBlack\Controllers\AuthController@sessionCreation');
            $router->get('markets/panda-black/expire-time', 'PandaBlack\Controllers\AuthController@tokenExpireTime');

            //Category Actions
            $router->get('markets/panda-black/parent-categories', 'PandaBlack\Controllers\CategoryController@all');
            $router->get('markets/panda-black/parent-categories/{id}', 'PandaBlack\Controllers\CategoryController@get');
            $router->get('markets/panda-black/vendor-categories', 'PandaBlack\Controllers\CategoryController@getPBCategories');
            $router->get('markets/panda-black/correlations', 'PandaBlack\Controllers\CategoryController@getCorrelations');
            $router->post('markets/panda-black/edit-correlations', 'PandaBlack\Controllers\CategoryController@updateCorrelation');
            $router->post('markets/panda-black/create-correlation', 'PandaBlack\Controllers\CategoryController@saveCorrelation');
            $router->delete('markets/panda-black/correlations/delete', 'PandaBlack\Controllers\CategoryController@deleteAllCorrelations');
            $router->delete('markets/panda-black/correlation/delete/{id}', 'PandaBlack\Controllers\CategoryController@deleteCorrelation');

            //Attribute Actions
            $router->post('markets/panda-black/create-attribute/{id}', 'PandaBlack\Controllers\AttributeController@createPBAttributes');
            $router->get('markets/panda-black/vendor-attribute/{categoryId}', 'PandaBlack\Controllers\AttributeController@getPBAttributes');

            //Sending Content Actions
            $router->get('markets/panda-black/products-data', 'PandaBlack\Controllers\ContentController@productDetails');
        });
    }
}