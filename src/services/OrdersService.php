<?php

/**
 * Commerce Insights Orders Service
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types = 1);

namespace fostercommerce\commerceinsights\services;

use fostercommerce\commerceinsights\CommerceInsights;
use fostercommerce\commerceinsights\helpers\Helpers;
use fostercommerce\commerceinsights\models\OrdersModel;
use fostercommerce\commerceinsights\controllers\StatsController;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;

class OrdersService extends Component
{
    private $dates;
    // filters
    private $keyword;
    private $orderType;
    private $paymentType;
    // misc
    private $orders = [];

    /**
     * Constructor. Sets up all of the properties for this class based on $_GET and
     * $_POST data, and fetches the orders when the class is intantiated.
     *
     * @return void
     */
    public function __construct($id, $module, $config = []) {
        $this->dates = Helpers::getDateRangeData();

        // Filters that may be set
        $this->keyword     = Craft::$app->request->getBodyParam('keyword');
        $this->orderType   = Craft::$app->request->getBodyParam('orderType');
        $this->paymentType = Craft::$app->request->getBodyParam('paymentType');

        // Go get 'em, tiger
        $this->orders = $this->fetchOrders();

        parent::__construct($id, $module, $config);
    }

    /**
     * Fetches the orders based on the given criteria established in the constructor.
     *
     * @param int $productId - If you want to fetch all the orders for a product
     *
     * @return array
     */
    public function fetchOrders(?int $productId = null): array {
        $orders = Order::find()->distinct()->orderBy('dateOrdered desc');

        if ($productId) {
            $product = Variant::find()->id($productId)->one();
            $orders->hasPurchasables([$product]);
        }

        if ($this->keyword) {
            $orders->search($this->keyword);
        }

        if ($this->orderType) {
            $orders->orderStatus(strtolower($this->orderType));
        }

        if ($this->paymentType) {
            $orders->where(['paidStatus' => strtolower($paymentType)]);
        }

        return [
            'previousPeriod' => $orders->dateOrdered(['and', ">= {$this->dates['previousStart']}", "< {$this->dates['originalStart']}"])->all(),
            'currentPeriod'  => $orders->dateOrdered(['and', ">= {$this->dates['originalStart']}", "< {$this->dates['originalEnd']}"])->all()
        ];
    }

    /**
     * Returns the orders as formatted by the model.
     *
     * @return array
     */
    public function getOrders(): array {
        $model = new OrdersModel();
        $statsData = [
            'type'  => 'orders',
            'data'  => $this->orders,
            'start' => $this->dates['previousStart'],
            'end'   => $this->dates['originalEnd']
        ];
        $result = [
            'orders' => $model->getOrderData($this->orders),
            'stats'  => CommerceInsights::$plugin->stats->getStats($statsData)
        ];

        return $result;
    }
}
