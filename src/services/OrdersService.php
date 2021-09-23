<?php

/**
 * Commerce Insights Components Orders Service
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types = 1);

namespace fostercommerce\commerceinsights\services;

use fostercommerce\commerceinsights\CommerceInsights;
use fostercommerce\commerceinsights\models\OrdersModel;

use DateTime;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;
use fostercommerce\commerceinsights\models\StatsModel;

class OrdersService extends Component
{
    private $today;
    private $weekAgo;
    // Posted from the date range picker
    private $rangeStart;
    private $rangeEnd;
    // Passed as query params
    private $startQuery;
    private $endQuery;
    // The original dates selected, formatted
    private $originalStart;
    private $originalEnd;
    // The previous period
    private $previousStart;
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
        // Query params if they exist in the URL
        $startQuery = Craft::$app->request->getQueryParam('startDate') ?? null;
        $endQuery   = Craft::$app->request->getQueryParam('endDate') ?? null;

        // URL decode the query params if they are present
        // TODO: This might override the selected range? Or vice versa. Not implemented yet.
        $this->startQuery = $startQuery ? urldecode($startQuery) : null;
        $this->endQuery   = $endQuery ? urldecode($endQuery) : null;

        // All the formatted dates for the selected range (currently selected period)
        // If none is selected, we just do a week
        $this->today      = new DateTime(date('Y-m-d 23:59:59'));
        $this->weekAgo    = $this->today->modify('-7 day')->format('Y-m-d 00:00:00');
        $this->rangeStart = Craft::$app->request->getBodyParam('range_start') ?? null;

        // Either the selected end date, or today
        $this->rangeEnd = Craft::$app->request->getBodyParam('range_end') ?? $this->today->format('Y-m-d 23:59:59');

        // This will be either the selected start date, or a week ago if none is selected
        $this->originalStart = $this->rangeStart ?
            DateTime::createFromFormat('Y-m-d H:i:s', $this->rangeStart)->format('Y-m-d 00:00:00') :
            DateTime::createFromFormat('Y-m-d H:i:s', $this->weekAgo)->format('Y-m-d 00:00:00');

        // This is either the selected end date or today
        $this->originalEnd = DateTime::createFromFormat('Y-m-d H:i:s', $this->rangeEnd)->format('Y-m-d 23:59:59');

        // Set up the new dates based on the full range and the previous period of the same number of days
        $start = DateTime::createFromFormat('Y-m-d H:i:s', $this->originalStart);
        $end   = DateTime::createFromFormat('Y-m-d H:i:s', $this->originalEnd);

        // Number of days in the range
        $numDays = $end->diff($start)->format("%r%a");

        // TThe start date for the previous period
        $this->previousStart = $start->modify($numDays . ' day')->format('Y-m-d 00:00:00');

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
     * @return array
     */
    private function fetchOrders(): array {
        $orders = Order::find()->distinct()->orderBy('dateOrdered desc');

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
            'previousPeriod' => $orders->dateOrdered(['and', ">= {$this->previousStart}", "< {$this->originalStart}"])->all(),
            'currentPeriod'  => $orders->dateOrdered(['and', ">= {$this->originalStart}", "< {$this->originalEnd}"])->all()
        ];
    }

    /**
     * Returns the orders as formatted by the model.
     *
     * @return array
     */
    public function getOrders(): array {
        $model  = new OrdersModel();
        $statsData = [
            'orders'        => $this->orders,
            'start'         => $this->previousStart,
            'end'           => $this->originalEnd,
            'previousStart' => $this->previousStart
        ];
        $result = [
            'orders' => $model->getOrderData($this->orders),
            'stats'  => CommerceInsights::$plugin->stats->getStats($statsData)
        ];

        return $result;
    }
}
