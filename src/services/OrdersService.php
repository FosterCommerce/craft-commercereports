<?php

/**
 * Commerce Reports Orders Service
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types=1);

namespace fostercommerce\commercereports\services;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;

use craft\commerce\elements\Variant;
use fostercommerce\commercereports\CommerceReports;
use fostercommerce\commercereports\helpers\Helpers;
use fostercommerce\commercereports\models\OrderModel;

class OrdersService extends Component
{
    protected $dates;
    // filters
    protected $keyword;
    protected $orderType;
    protected $paymentType;

    /**
     * Constructor. Sets up all of the properties for this class based on $_GET and
     * $_POST data, and fetches the orders when the class is intantiated.
     *
     * @return void
     */
    public function __construct($config = [])
    {
        $this->dates = Helpers::getDateRangeData();

        // Filters that may be set
        $this->keyword = Craft::$app->request->getBodyParam('keyword');
        $this->orderType = Craft::$app->request->getBodyParam('orderType');
        $this->paymentType = Craft::$app->request->getBodyParam('paymentType');

        parent::__construct($config);
    }

    /**
     * Fetches the orders based on the given criteria established in the constructor.
     *
     * @param array $opts - [
     *   int  'id'           - Product ID if you want to fetch all the orders for a product
     *   bool 'withPrevious' - Whether or not you want to fetch the previous period's orders
     * ]
     *
     * @return array
     */
    public function fetchOrders(array $opts = []): array
    {

        $productId = $opts['productId'] ?? null;
        $withPrevious = $opts['withPrevious'] ?? true;
        $withAddresses = $opts['withAddresses'] ?? false;
        
        $orders = Order::find()->distinct()->orderBy('dateOrdered desc');
        $result = [];

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
            $orders->where(['paidStatus' => strtolower($this->paymentType)]);
        }

        $orders->dateOrdered(['and', ">= {$this->dates['originalStart']}", "< {$this->dates['originalEnd']}"]);

        $orders->select([
            'id', 
            'dateOrdered',
            'orderStatusId',
            'email',
            'number',
            'reference',
        ]);

        if ($withAddresses) $orders->addSelect(['billingAddressId', 'shippingAddressId']);

        $result = $orders->all();

        if ($withPrevious) {
            $result = [
                'previousPeriod' => $orders->dateOrdered(['and', ">= {$this->dates['previousStart']}", "< {$this->dates['originalStart']}"])->all(),
                'currentPeriod' => $result,
            ];
        }

        return $result;
    }

    /**
     * Returns the orders as formatted by the model.
     *
     * @return array
     */
    public function getOrders(): array
    {
        $orders = $this->fetchOrders();
        $statsData = [
            'type' => 'orders',
            'data' => $orders,
            'start' => $this->dates['originalStart'],
            'end' => $this->dates['originalEnd'],
        ];
        $result = [
            'orders' => OrderModel::fromOrders($orders),
            'stats' => CommerceReports::$plugin->stats->getStats($statsData),
        ];

        return $result;
    }
}
