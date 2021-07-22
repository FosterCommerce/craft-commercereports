<?php

namespace fostercommerce\commerceinsights\controllers;

use Craft;
use DateTime;
use craft\web\Controller;
use craft\commerce\elements\Order;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\commerce\helpers\Currency;

class VueController extends Controller
{
    private $stats;
    private $orders;
    private $products;
    private $variants;
    private $customers;
    protected $start_date;
    protected $end_date;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);

        $today          = new DateTime(date('Y-m-d'));
        $weekAgo        = new DateTime(date('Y-m-d'));
        $weekAgo        = $weekAgo->modify('-7 day')->format('Y-m-d 00:00:00');
        $rangeStart     = Craft::$app->request->getBodyParam('range_start');
        $this->end_date = Craft::$app->request->getBodyParam('range_end') ?? $today->format('Y-m-d 23:59:59');

        $this->start_date = $rangeStart ?
            DateTime::createFromFormat('Y-m-d H:i:s', $rangeStart)->format('Y-m-d 00:00:00') :
            DateTime::createFromFormat('Y-m-d H:i:s', $weekAgo)->format('Y-m-d 00:00:00');
    }

    public function actionIndex($view)
    {
        return $this->renderTemplate('commerceinsights/vue/index', [
            'navItem' => $view,
        ]);
    }

    public function actionGetStats()
    {
        return $this->asJson($this->_getStats());
    }

    public function actionGetOrders()
    {
        return $this->asJson($this->_getOrders());
    }

    /**
     * Converts a number into a string with the proper currency symbol
     *
     * @param float     $amount     - The amount to convert
     * @param string    $currency   - The currency
     * @param bool      $convert    - Whether to convert to the payment currency
     *
     * @return string
     */
    protected static function convertCurrency(float $amount, string $currency, bool $convert = true) : string
    {
        $amt = Currency::formatAsCurrency($amount, $currency, $convert);

        if (strpos($amt, '-')) {
            $amt = str_replace('-', '', $amt);
            $amt = '-' . $amt;
        }

        return $amt;
    }

    /**
     * Get all orders in the selected date range (defined in the constructor).
     * If no date range is set, it will fetch all orders for all time.
     *
     * @return array - All orders in range, or an empty array
     */
    private function fetchOrders($id = null) : array
    {
        $single       = Craft::$app->request->getQueryParam('purchasableId') ?? $id;
        $currentStart = DateTime::createFromFormat('Y-m-d H:i:s', $this->start_date)->format('Y-m-d 00:00:00');
        $start        = DateTime::createFromFormat('Y-m-d H:i:s', $this->start_date);
        $end          = DateTime::createFromFormat('Y-m-d H:i:s', $this->end_date);
        // nuber of days in selected range
        $numDays  = $end->diff($start)->format("%r%a");
        // get the new start date based on what the previous period would be
        $newStart = $start->modify($numDays . ' day')->format('Y-m-d 00:00:00');
        $newEnd   = $end->modify('1 day')->format('Y-m-d 00:00:00');
        // query the previous period and selected range based on new start date
        $orders   = Order::find()->dateOrdered(['and', ">= {$newStart}", "< {$newEnd}"])->distinct()->orderBy('dateOrdered desc');
        $result   = [];

        if ($single) {
            $single = Variant::find()->id($single)->one();
            $orders->hasPurchasables([$single]);
            $orders->orderStatusId('< 4');
        }

        $result['previousPeriod'] = $orders->dateOrdered(['and', ">= {$newStart}", "< {$currentStart}"])->all();
        $result['currentPeriod']  = $orders->dateOrdered(['and', ">= {$currentStart}", "< {$newEnd}"])->all();

        return $result;
    }

    /**
     * Get all products that are in the system.
     *
     * @return array - All products, or an empty array
     */
    private function fetchProducts() : array
    {
        return Product::find()->anyStatus()->all() ?: [];
    }

    /**
     * Get all variants that are in the system.
     *
     * @return array - All variants, or an empty array
     */
    private function fetchVariants() : array
    {
        return Variant::find()->anyStatus()->all() ?: [];
    }

    private function _getStats()
    {
        $orders            = $this->fetchOrders();
        $previousOrders    = $orders['previousPeriod'];
        $currentOrders     = $orders['currentPeriod'];
        $numPreviousOrders = count($previousOrders);
        $numCurrentOrders  = count($currentOrders);
        $previousRevenue   = 0;
        $currentRevenue    = 0;

        foreach ($previousOrders as $order) {
            $previousRevenue += $order->totalPaid;
        }

        foreach ($currentOrders as $order) {
            $currentRevenue += $order->totalPaid;
        }

        $result         = [
            'orders' => [
                // This is for the paragraph data
                // we probably don't need this but the component needs changed to look at orders data
                'summary' => [
                    'ordersPercentChange' => 0
                ],
                // This is in the customers view
                'topLocations' => [
                    [
                        'country' => 'US',
                        'destination' => 'New York, NY',
                        'total' => 785
                    ],
                    [
                        'country' => 'US',
                        'destination' => 'Columbus, OH',
                        'total' => 512
                    ],
                    [
                        'country' => 'US',
                        'destination' => 'Seattle, WA',
                        'total' => 472
                    ],
                    [
                        'country' => 'ES',
                        'destination' => 'Madrid',
                        'total' => 405
                    ],
                    [
                        'country' => 'US',
                        'destination' => 'San Francisco, CA',
                        'total' => 322
                    ],
                    [
                        'country' => 'US',
                        'destination' => 'Austin, TX',
                        'total' => 284
                    ],
                    [
                        'country' => 'US',
                        'destination' => 'New Orleans, LA',
                        'total' => 142
                    ]
                ],
                'totalOrders' => [
                    'total' => $numCurrentOrders,
                    // this is based on the new previous period data
                    'percentChange' => round((($numCurrentOrders - $numPreviousOrders) / $numPreviousOrders) * 100, 2),
                    'revenue' => round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2),
                    'series' => [32, 40, 43, 45, 300, 56, 60, 80, 90, 105]
                ],
                // averageOrderValue, averageOrderQuantity
                'averageValue' => [
                    'total' => 98.76,
                    'percentChange' => -3,
                    'series' => [32, 40, 43, 45, 49, 56, 60, 80, 90, 105]
                ],
                'averageQuantity' => [
                    'total' => 4,
                    'percentChange' => 25,
                    'series' => [32, 40, 43, 45, 49, 56, 60, 80, 90, 105]
                ],
                'totalCustomers' => [
                    'total' => 479,
                    'percentChange' => 8,
                    'series' => [32, 40, 43, 45, 49, 56, 60, 80, 90, 105]
                ],
                'newCustomers' => [
                    'total' => 80,
                    'percentChange' => 8,
                    'series' => [32, 40, 43, 45, 49, 56, 60, 80, 90, 105]
                ],
                'returningCustomers' => [
                    'total' => 399,
                    'percentChange' => 8,
                    'series' => [32, 40, 43, 45, 49, 56, 60, 80, 90, 105]
                ]
            ]
        ];

        return $result;
    }

    protected function _getOrders($id = null)
    {
        $orders = $this->fetchOrders($id);
        $result = [];

        foreach ($orders['currentPeriod'] as $idx => $order) {
            $currency   = $order->currency;
            $line_items = $order->lineItems;
            $result[]   = [
                'orderId'       => $order->id,
                'reference'     => $order->friendlyOrderNumber ?? $order->reference,
                'date'          => $order->dateOrdered->format('m/d/Y g:ia'),
                'fullDate'      => $order->dateOrdered->format('l, F j, Y, g:ia'),
                'dateStamp'     => $order->dateOrdered->getTimestamp(),
                'status'        => $order->orderStatus->name,
                'color'         => $order->orderStatus->color,
                'base'          => static::convertCurrency(($order->total - $order->adjustmentSubtotal), $currency),
                'merchTotal'    => static::convertCurrency(($order->total - $order->totalTax - $order->totalShippingCost - $order->totalDiscount), $currency),
                'tax'           => static::convertCurrency($order->totalTax, $currency),
                'shipping'      => static::convertCurrency($order->totalShippingCost, $currency),
                'discount'      => static::convertCurrency($order->totalDiscount, $currency),
                'amountPaid'    => static::convertCurrency($order->totalPaid, $currency),
                'paymentStatus' => ucwords($order->paidStatus),
                'paidColor'     => $order->paidStatus === 'paid' ? 'green' : 'red',
                'email'         => $order->customer->email,
                'billingName'   => ($order->billingAddress->firstName ?? ' ') . ' ' . ($order->billingAddress->lastName ?? ' '),
                'shippingName'  => ($order->shippingAddress->firstName ?? ' ') . ' ' . ($order->shippingAddress->lastName ?? ' '),
                'numItems'      => 0
            ];

            foreach ($line_items as $item) {
                $result[$idx]['numItems'] += $item->qty;
            }
        }

        return $result;
    }
}
