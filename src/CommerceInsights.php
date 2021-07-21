<?php
/**
 * Commerce Insights Components plugin for Craft CMS 3.x
 *
 * Throwaway demo / integration project.
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2019 Foster Commerce
 */

namespace fostercommerce\commerceinsights;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;

use yii\base\Event;

class CommerceInsights extends Plugin {
    public static $plugin;

    public $hasCpSection = true;
    public $schemaVersion = '1.0.0';

    public function init() {
        parent::init();
        self::$plugin = $this;

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                /*
                 * Orders
                 */
                $event->rules['commerceinsights/orders']                       = 'commerceinsights/order/order-index';
                $event->rules['commerceinsights/orders/<format:json>']         = 'commerceinsights/order/order-index';
                $event->rules['commerceinsights/orders/<format:csv>']          = 'commerceinsights/order/order-index';
                $event->rules['commerceinsights/orders/product/<id:([0-9])+>'] = 'commerceinsights/order/product';

                /*
                 * Products
                 */
                $event->rules['commerceinsights/products']               = 'commerceinsights/product/product-index';
                $event->rules['commerceinsights/products/<format:json>'] = 'commerceinsights/product/product-index';
                $event->rules['commerceinsights/products/<format:csv>']  = 'commerceinsights/product/product-index';

                /*
                 * Sales
                 */
                $event->rules['commerceinsights/sales']               = 'commerceinsights/sales/sales-index';
                $event->rules['commerceinsights/sales/<format:json>'] = 'commerceinsights/sales/sales-index';
                $event->rules['commerceinsights/sales/<format:csv>']  = 'commerceinsights/sales/sales-index';

                /*
                 * Customers
                 */
                $event->rules['commerceinsights/customers']               = 'commerceinsights/customer/customer-index';
                $event->rules['commerceinsights/customers/<format:json>'] = 'commerceinsights/customer/customer-index';
                $event->rules['commerceinsights/customers/<format:csv>']  = 'commerceinsights/customer/customer-index';

                /*
                 * Saved
                 */
                $event->rules['commerceinsights/saved']             = 'commerceinsights/saved/list';
                $event->rules['commerceinsights/saved/save']        = 'commerceinsights/saved/save-report';
                $event->rules['commerceinsights/saved/<id>']        = 'commerceinsights/saved/get-report';
                $event->rules['commerceinsights/saved/delete/<id>'] = 'commerceinsights/saved/delete-report';

                // new vue stuff
                $event->rules['commerceinsights/view/<view>'] = 'commerceinsights/vue/index';
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
                'url'   => 'commerceinsights/view/sales'
            ],
            'products'  => [
                'label' => Craft::t('commerceinsights', 'Product Insights'),
                'url'   => 'commerceinsights/view/products'
            ],
            'customers' => [
                'label' => Craft::t('commerceinsights', 'Customers'),
                'url'   => 'commerceinsights/view/customers'
            ],
            'saved'     => [
                'label' => Craft::t('commerceinsights', 'Saved'),
                'url'   => 'commerceinsights/view/saved'
            ]
        ];

        return $item;
    }
}
