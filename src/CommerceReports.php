<?php
/**
 * Commerce Reports plugin for Craft CMS 3.x
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types = 1);

namespace fostercommerce\commercereports;

use fostercommerce\commercereports\services\OrdersService;
use fostercommerce\commercereports\services\ProductService;
use fostercommerce\commercereports\services\StatsService;
use fostercommerce\commercereports\services\ItemsSoldService;
use fostercommerce\commercereports\services\CustomersService;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;

use yii\base\Event;

class CommerceReports extends Plugin
{
    public static $plugin;

    public $hasCpSection = true;
    public $schemaVersion = '1.0.1';

    public function __construct($id, $parent = null, array $config = []) {
        $config['components'] = [
            'stats'     => StatsService::class,
            'orders'    => OrdersService::class,
            'product'   => ProductService::class,
            'itemsSold' => ItemsSoldService::class,
            'customers' => CustomersService::class
        ];

        parent::__construct($id, $parent, $config);
    }

    public function init() {
        parent::init();
        self::$plugin = $this;

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                /*
                 * Vue templates
                 */
                $event->rules['commercereports/view/orders']                  = 'commercereports/orders/index';
                $event->rules['commercereports/orders/product/<id:([0-9])+>'] = 'commercereports/product/index';
                $event->rules['commercereports/view/items-sold']              = 'commercereports/items-sold/index';
                $event->rules['commercereports/view/customers']               = 'commercereports/customers/index';
            }
        );

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                /*
                 * AJAX routes
                 */
                $event->rules['get-ci-orders']     = 'commercereports/orders/get-orders';
                $event->rules['get-ci-items-sold'] = 'commercereports/items-sold/get-items-sold';
                $event->rules['get-ci-product']    = 'commercereports/product/get-product';
                $event->rules['get-ci-customers']  = 'commercereports/customers/get-customers';

                if (Craft::$app->plugins->isPluginEnabled('commerce-reports-extensions')) {
                    $event->rules['get-ci-items-sold'] = 'commerce-reports-extensions/items-sold-extension/get-items-sold';
                    $event->rules['get-ci-product']    = 'commerce-reports-extensions/product-extension/get-product';
                }
            }
        );
    }

    public function getCpNavItem() {
        $item               = parent::getCpNavItem();
        $item['label']      = Craft::t('commercereports', 'Commerce Reports');
        $item['badgeCount'] = 0;
        $item['subnav']     = [
            'orders'    => [
                'label' => Craft::t('commercereports', 'Orders'),
                'url'   => 'commercereports/view/orders'
            ],
            'items-sold' => [
                'label'  => Craft::t('commercereports', 'Items Sold'),
                'url'    => 'commercereports/view/items-sold'
            ],
            'customers' => [
                'label' => Craft::t('commercereports', 'Customers'),
                'url'   => 'commercereports/view/customers'
            ]
        ];

        return $item;
    }
}
