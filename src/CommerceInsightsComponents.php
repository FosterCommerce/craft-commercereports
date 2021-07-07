<?php
/**
 * Commerce Insights Components plugin for Craft CMS 3.x
 *
 * Throwaway demo / integration project.
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2019 Foster Commerce
 */

namespace fostercommerce\commerceinsightscomponents;


use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;

use yii\base\Event;

class CommerceInsightsComponents extends Plugin
{
    public static $plugin;

    public $hasCpSection = true;
    public $schemaVersion = '1.0.0';

    public function init() {
        parent::init();
        self::$plugin = $this;

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
            // $event->rules['commerceinsights'] = 'commerceinsights/dashboard/dashboard-index';

	        /*
	         * Revenue
	         */
            /*$event->rules['commerceinsightscomponents/revenue'] = 'commerceinsightscomponents/revenue/revenue-index';
            $event->rules['commerceinsightscomponents/revenue/<format:json>'] = 'commerceinsightscomponents/revenue/revenue-index';
            $event->rules['commerceinsightscomponents/revenue/<format:csv>'] = 'commerceinsightscomponents/revenue/revenue-index';*/

	        /*
			 * Orders
			 */
            $event->rules['commerceinsightscomponents/orders'] = 'commerceinsightscomponents/order/order-index';
            $event->rules['commerceinsightscomponents/orders/<format:json>'] = 'commerceinsightscomponents/order/order-index';
            $event->rules['commerceinsightscomponents/orders/<format:csv>'] = 'commerceinsightscomponents/order/order-index';
            $event->rules['commerceinsightscomponents/orders/product/<id:([0-9])+>'] = 'commerceinsightscomponents/order/product';

            /*
             * Products
             */
            $event->rules['commerceinsightscomponents/products'] = 'commerceinsightscomponents/product/product-index';
            $event->rules['commerceinsightscomponents/products/<format:json>'] = 'commerceinsightscomponents/product/product-index';
            $event->rules['commerceinsightscomponents/products/<format:csv>'] = 'commerceinsightscomponents/product/product-index';

            /*
             * Sales
             */
            $event->rules['commerceinsightscomponents/sales'] = 'commerceinsightscomponents/sales/sales-index';
            $event->rules['commerceinsightscomponents/sales/<format:json>'] = 'commerceinsightscomponents/sales/sales-index';
            $event->rules['commerceinsightscomponents/sales/<format:csv>'] = 'commerceinsightscomponents/sales/sales-index';

	        /*
			 * Customers
			 */
	        $event->rules['commerceinsightscomponents/customers'] = 'commerceinsightscomponents/customer/customer-index';
	        $event->rules['commerceinsightscomponents/customers/<format:json>'] = 'commerceinsightscomponents/customer/customer-index';
	        $event->rules['commerceinsightscomponents/customers/<format:csv>'] = 'commerceinsightscomponents/customer/customer-index';

	        /*
			 * Saved
			 */
            /*$event->rules['commerceinsightscomponents/saved'] = 'commerceinsightscomponents/saved/list';
            $event->rules['commerceinsightscomponents/saved/save'] = 'commerceinsightscomponents/saved/save-report';
            $event->rules['commerceinsightscomponents/saved/<id>'] = 'commerceinsightscomponents/saved/get-report';
            $event->rules['commerceinsightscomponents/saved/delete/<id>'] = 'commerceinsightscomponents/saved/delete-report';*/

            // new vue stuff
            $event->rules['commerceinsightscomponents/view/<view>'] = 'commerceinsightscomponents/vue/index';
        });
    }

    public function getCpNavItem() {
        $item = parent::getCpNavItem();
        $item['label'] = Craft::t('commerceinsightscomponents', 'Commerce Insights');
        $item['badgeCount'] = 0;
        $item['subnav'] = [
            //'dashboard' => ['label' => Craft::t('commerceinsightscomponents', 'Dashboard'), 'url' => 'commerceinsightscomponents/view/dashboard'],
            //'revenue' => ['label' => Craft::t('commerceinsightscomponents', 'Revenue'), 'url' => 'commerceinsightscomponents/view/revenue'],
            'orders'    => ['label' => Craft::t('commerceinsightscomponents', 'Orders'), 'url' => 'commerceinsightscomponents/view/orders'],
            'sales'     => ['label' => Craft::t('commerceinsightscomponents', 'Items Sold'), 'url' => 'commerceinsightscomponents/view/sales'],
            //'products'  => ['label' => Craft::t('commerceinsightscomponents', 'Product Insights'), 'url' => 'commerceinsightscomponents/view/products'],
            'customers' => ['label' => Craft::t('commerceinsightscomponents', 'Customers'), 'url' => 'commerceinsightscomponents/view/customers'],
            //'saved' => ['label' => Craft::t('commerceinsightscomponents', 'Saved'), 'url' => 'commerceinsightscomponents/view/saved']
        ];

        return $item;
    }
}
