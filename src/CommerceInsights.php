<?php
/**
 * Commerce Insights plugin for Craft CMS 3.x
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types = 1);

namespace fostercommerce\commerceinsights;

use fostercommerce\commerceinsights\services\OrdersService;
use fostercommerce\commerceinsights\services\StatsService;
use fostercommerce\commerceinsights\services\ItemsSoldService;
use fostercommerce\commerceinsights\services\CustomersService;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;

use yii\base\Event;

class CommerceInsights extends Plugin
{
    public static $plugin;

    public $hasCpSection = true;
    public $schemaVersion = '1.0.0';

    public function __construct($id, $parent = null, array $config = []) {
        $config['components'] = [
            'stats'     => StatsService::class,
            'orders'    => OrdersService::class,
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
                $event->rules['commerceinsights/view/orders']     = 'commerceinsights/orders/index';
                $event->rules['commerceinsights/view/items-sold'] = 'commerceinsights/items-sold/index';
                $event->rules['commerceinsights/view/customers']  = 'commerceinsights/customers/index';
            });
    }

    public function getCpNavItem() {
        $item               = parent::getCpNavItem();
        $item['label']      = Craft::t('commerceinsights', 'Commerce Insights');
        $item['badgeCount'] = 0;
        $item['subnav']     = [
            'orders'    => [
                'label' => Craft::t('commerceinsights', 'Orders'),
                'url'   => 'commerceinsights/view/orders'
            ],
            'sales'     => [
                'label' => Craft::t('commerceinsights', 'Items Sold'),
                'url'   => 'commerceinsights/view/items-sold'
            ],
            'customers' => [
                'label' => Craft::t('commerceinsights', 'Customers'),
                'url'   => 'commerceinsights/view/customers'
            ]
        ];

        return $item;
    }
}
