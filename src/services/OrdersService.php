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
use fostercommerce\commerceinsights\models\OrderModel;
use fostercommerce\commerceinsights\controllers\StatsController;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;
use craft\helpers\Db;

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
    public function __construct($id, $module, $config = []) {
        $this->dates = Helpers::getDateRangeData();

        // Filters that may be set
        $this->keyword     = Craft::$app->request->getBodyParam('keyword');
        $this->orderType   = Craft::$app->request->getBodyParam('orderType');
        $this->paymentType = Craft::$app->request->getBodyParam('paymentType');

        parent::__construct($id, $module, $config);
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
    public function fetchOrders(array $opts = []): array {
        $productId    = $opts['productId'] ?? null;
        $withPrevious = $opts['withPrevious'] ?? true;
        $result       = [];
        
        //$orders       = Order::find()->distinct()->orderBy('dateOrdered desc');
        
        $orders = (new \craft\db\Query())
            ->select([
                'o.id',
                'o.reference',
                'o.dateOrdered',
                'os.name as `orderStatus`',
                'o.itemTotal',
                'o.totalTax',
                'o.totalTaxIncluded',
                'o.totalDiscount',
                'o.totalShippingCost',
                'o.totalPaid',
                '(select sum(l.qty) from commerce_lineitems l where l.orderId = o.id) as `totalItemsSold`',
                'o.paidStatus'
            ])
            ->from('commerce_orders o')
            ->join('LEFT JOIN', 'commerce_orderstatuses os', 'os.id = o.orderStatusId')
            ->orderBy('o.dateOrdered desc');
            //->all();
        
        
        /*$query = Db::db()->createCommand("select 
        o.id as 'id',
        o.reference as 'Order #',
        o.dateOrdered as 'Date Ordered',
        os.name as 'Status',
        o.itemTotal as 'Merchandise Total',
        o.totalTax as 'Tax',
        o.totalTaxIncluded as 'Total Tax Included',
        o.totalDiscount as 'Discount',
        o.totalShippingCost as 'Shipping',
        o.totalPaid as 'Total Paid',
        (select sum(l.qty) from `commerce_lineitems` l where l.orderId = o.id)  as 'Total Items Sold',
        o.paidStatus as 'Payment Status'
        from commerce_orders o
        left join commerce_orderstatuses os on os.id = o.orderStatusId
        #left join commerce_lineitems l on l.orderId = o.id
        order by o.dateOrdered desc");
        
        $orders = $query->queryAll();
        
        
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

        $result = $orders->dateOrdered(['and', ">= {$this->dates['originalStart']}", "< {$this->dates['originalEnd']}"])->all();

        if ($withPrevious) {
            $result = [
                'previousPeriod' => $orders->dateOrdered(['and', ">= {$this->dates['previousStart']}", "< {$this->dates['originalStart']}"])->all(),
                'currentPeriod'  => $result
            ];
        }
        
        */
        
        if ($this->keyword) {
            $orders->search($this->keyword);
        }
        
        if ($this->orderType) {
            $orders->where("o.orderStatus = :orderStatus", [':orderStatus' => strtolower($this->paymentType)]);
        }
        
        if ($this->paymentType) {
            $orders->where("o.orderStatus = :orderStatus", [':orderStatus' => strtolower($this->orderType)]);
        }
        
        $orders->where("o.dateOrdered >= :startDate and o.dateOrdered < :endDate", [':startDate' => $this->dates['originalStart'], ':endDate' => $this->dates['originalEnd']]);
        
        
        $result['currentPeriod'] = $orders->all();

        if($withPrevious) {
            $result['previousPeriod'] = $orders->where("o.dateOrdered >= :startDate and o.dateOrdered < :endDate", [':startDate' => $this->dates['previousStart'], ':endDate' => $this->dates['originalStart']])->all();
        }
        
        return $result;
    }

    /**
     * Returns the orders as formatted by the model.
     *
     * @return array
     */
    public function getOrders(): array {
        $orders    = $this->fetchOrders();
        $statsData = [
            'type'  => 'orders',
            'data'  => $orders,
            'start' => $this->dates['originalStart'],
            'end'   => $this->dates['originalEnd']
        ];
        $result = [
            'orders' => OrderModel::fromOrders($orders),
            'stats'  => CommerceInsights::$plugin->stats->getStats($statsData)
        ];

        return $result;
    }
}
