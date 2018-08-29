 
<?php // strict

namespace PandaBlack\Providers;

use Plenty\Plugin\Events\Dispatcher;
use Plenty\Plugin\ServiceProvider;
use Plenty\Plugin\Templates\Twig;


class PandaBlackServiceProvider extends ServiceProvider
{
    /**
     * Register the core functions
     */
    public function register()
    {
    }

    /**
     * @param Twig $twig
     * @param Dispatcher $eventDispatcher
     */
    public function boot(Twig $twig, Dispatcher $eventDispatcher)
    {
    }
}
