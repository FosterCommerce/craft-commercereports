<?php

/**
 * Commerce Insights Product Service
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types = 1);

namespace fostercommerce\commerceinsights\services;

use fostercommerce\commerceinsights\CommerceInsights;
use fostercommerce\commerceinsights\helpers\Helpers;
use fostercommerce\commerceinsights\models\OrdersModel;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;

class ProductService extends Component
{
    private $dates;
    private $id;
    private $orders = [];

    /**
     * Constructor. Sets up all of the properties for this class based on $_GET and
     * $_POST data, and fetches the orders when the class is intantiated.
     *
     * @return void
     */
    public function __construct($id, $module, $config = []) {
        $this->dates = Helpers::getDateRangeData();
        $this->id    = (int)Craft::$app->request->getQueryParam('id');

        // Go get 'em, tiger
        $this->orders = $this->fetchProduct();

        parent::__construct($id, $module, $config);
    }

    /**
     * Fetches the orders based on the given criteria established in the constructor.
     *
     * @return array
     */
    public function fetchProduct(): array {
        $orders    = CommerceInsights::$plugin->orders->fetchOrders($this->id);
        $model     = new OrdersModel();
        $statsData = [
            'type'  => 'orders',
            'data'  => $this->orders,
            'start' => $this->dates['previousStart'],
            'end'   => $this->dates['originalEnd']
        ];
        
        return $model->getOrderData($orders);
    }

    /**
     * Returns the orders for the product.
     *
     * @return array
     */
    public function getProduct(): array {
        return $this->fetchProduct();
    }
}
