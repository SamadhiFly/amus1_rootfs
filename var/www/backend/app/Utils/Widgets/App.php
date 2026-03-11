<?php
namespace App\Utils\Widgets;

use Illuminate\Database\Capsule\Manager as Manager;

/**
 * Class App
 * @package App\Utils\Widgets
 */
final class App
{


    /**
     * @var null
     */
    private static $instance = null;


    /**
     * @var array
     */
    private static $defaultConfig = [
        'settings' => [
            'displayErrorDetails'               => true,
            'determineRouteBeforeAppMiddleware' => true,
            'sqlite'    => [
                'default'   => [
                    'driver'    => 'sqlite',
                    'database'  => '/home/bird/data/admin.sqlite',
                    //'database'  => '/tmp/admin.sqlite',
                    'prefix'    => '',
                ],
                'config'   => [
                    'driver'    => 'sqlite',
                    'database'  => '/tmp/config.sqlite',
                    'prefix'    => '',
                ],
                'status'   => [
                    'driver'    => 'sqlite',
                    'database'  => '/tmp/current_status.sqlite',
                    'prefix'    => '',
                ],
                'events'   => [
                    'driver'    => 'sqlite',
                    'database'  => '/home/bird/data/events.sqlite',
                    'prefix'    => '',
                ],
            ],
        ],
    ];

    /**
     * @return \Slim\App|null
     */
    public static function getInstance()
    {
        if(!self::$instance) {
            //new container
            $container = new \Slim\Container(self::$defaultConfig);
            // add Illuminate package
            $container['sqlite'] = function ($container) {
                $capsule = new Manager;
                foreach ($container['settings']['sqlite'] as $key => $database) {
                    $capsule->addConnection($database,$key);
                }
                $capsule->setAsGlobal();
                $capsule->bootEloquent();
                return $capsule;
            };
            self::$instance = new \Slim\App($container);
        }
        return self::$instance;
    }

}