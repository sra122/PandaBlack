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

            $router->get('markets/panda-black/vendor-categories', 'PandaBlack\Controllers\CategoryController@getCategoriesList');

            //Attribute Actions
            $router->post('markets/panda-black/create-attribute/{id}', 'PandaBlack\Controllers\AttributeController@createPMAttributes');
            $router->get('markets/panda-black/vendor-attribute/{id}', 'PandaBlack\Controllers\AttributeController@getPBAttributes');
            $router->get('markets/panda-black/pm-properties', 'PandaBlack\Controllers\AttributeController@getPMProperties');
            $router->get('markets/panda-black/pm-property-values', 'PandaBlack\Controllers\AttributeController@getPMPropertyValues');

            //Sending Content Actions
            $router->post('markets/panda-black/products-data', 'PandaBlack\Controllers\ContentController@sendProductDetails');
            //$router->get('markets/panda-black/products-status', 'PandaBlack\Controllers\MappingController@getProperties');

            //PandaBlack Category as Property
            $router->post('markets/panda-black/create-category-as-property', 'PandaBlack\Controllers\PropertyController@createCategoryAsProperty');

            //mapping
            $router->post('markets/panda-black/mapping', 'PandaBlack\Controllers\MappingController@mapping');
            $router->get('markets/panda-black/mapping-data', 'PandaBlack\Controllers\MappingController@getProperties');

            //Notification
            $router->get('markets/panda-black/products-status', 'PandaBlack\Controllers\NotificationController@fetchProductsStatus');
            $router->get('markets/panda-black/notifications', 'PandaBlack\Controllers\NotificationController@fetchNotifications');
            $router->get('markets/panda-black/mark-notification/{id}', 'PandaBlack\Controllers\NotificationController@markAsRead');
        });
    }
}