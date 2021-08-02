<?php

namespace fostercommerce\commerceinsights\controllers;

use Craft;
use DateTime;
use DatePeriod;
use DateInterval;
use NumberFormatter;
use craft\web\Controller;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;
use Money\Money;
use Money\Currency;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;

class VueController extends Controller
{
    public function __construct($id, $module, $config = []) {
        parent::__construct($id, $module, $config);
    }

    public function actionIndex($view) {
        return $this->renderTemplate('commerceinsights/vue/index', [
            'navItem' => $view,
        ]);
    }

    public function actionGetStats() {
        return $this->asJson(self::_getStats());
    }

    public function actionGetOrders() {
        return $this->asJson(self::_getOrders());
    }

    public function actionGetItemsSold() {
        return $this->asJson(self::_getItemsSold());
    }

    /**
     * Converts a number into a string with the proper currency symbol
     *
     * @param int    $amount   - The amount to convert
     * @param string $currency - The currency
     *
     * @return string
     */
    protected static function convertCurrency(float $amount, string $currency) : string {
        $amount          = strpos($amount, '.') ? str_replace('.', '', $amount) : $amount . '00';
        $amount          = $amount === '000' ? 0 : $amount;
        $money           = new Money($amount, new Currency($currency));
        $currencies      = new ISOCurrencies();
        $numberFormatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        $moneyFormatter  = new IntlMoneyFormatter($numberFormatter, $currencies);

        return $moneyFormatter->format($money);
    }

    /**
     * Get all orders in the selected date range (defined in the constructor).
     * If no date range is set, it will fetch all orders for all time.
     *
     * @return array - All orders in range, or an empty array
     */
    private static function fetchOrders($id = null) : array {
        $today      = new DateTime(date('Y-m-d'));
        $weekAgo    = new DateTime(date('Y-m-d'));
        $weekAgo    = $weekAgo->modify('-7 day')->format('Y-m-d 00:00:00');
        $rangeStart = Craft::$app->request->getBodyParam('range_start');
        $endDate    = Craft::$app->request->getBodyParam('range_end') ?? $today->format('Y-m-d 23:59:59');
        $startDate  = $rangeStart ?
            DateTime::createFromFormat('Y-m-d H:i:s', $rangeStart)->format('Y-m-d 00:00:00') :
            DateTime::createFromFormat('Y-m-d H:i:s', $weekAgo)->format('Y-m-d 00:00:00');
        $currentStart = DateTime::createFromFormat('Y-m-d H:i:s', $startDate)->format('Y-m-d 00:00:00');
        $start        = DateTime::createFromFormat('Y-m-d H:i:s', $startDate);
        $end          = DateTime::createFromFormat('Y-m-d H:i:s', $endDate);
        // possible filters
        $keyword      = Craft::$app->request->getBodyParam('keyword');
        $orderType    = Craft::$app->request->getBodyParam('orderType');
        $paymentType  = Craft::$app->request->getBodyParam('paymentType');
        // number of days in selected range
        $numDays  = $end->diff($start)->format("%r%a");
        // get the new start date based on what the previous period would be
        $newStart = $start->modify($numDays . ' day')->format('Y-m-d 00:00:00');
        $newEnd   = $end->modify('1 day')->format('Y-m-d 00:00:00');
        // query the previous period and selected range based on new start date
        $orders   = Order::find()->distinct()->orderBy('dateOrdered desc');

        if ($keyword) {
            $orders->search($keyword);
        }

        if ($orderType) {
            $orders->orderStatus(strtolower($orderType));
        }

        if ($paymentType) {
            $orders->where(['paidStatus' => strtolower($paymentType)]);
        }

        if($id) {
            $product = Variant::find()->id($id)->one();
            $orders->hasPurchasables([$product]);
        }

        $result = [
            'previousPeriod' => $orders->dateOrdered(['and', ">= {$newStart}", "< {$currentStart}"])->all(),
            'currentPeriod'  => $orders->dateOrdered(['and', ">= {$currentStart}", "< {$newEnd}"])->all()
        ];

        return $result;
    }

    private static function _getStats() {
        $today      = new DateTime(date('Y-m-d'));
        $weekAgo    = new DateTime(date('Y-m-d'));
        $weekAgo    = $weekAgo->modify('-7 day')->format('Y-m-d 00:00:00');
        $rangeStart = Craft::$app->request->getBodyParam('range_start');
        $endDate    = Craft::$app->request->getBodyParam('range_end') ?? $today->format('Y-m-d 23:59:59');
        $startDate  = $rangeStart ?
            DateTime::createFromFormat('Y-m-d H:i:s', $rangeStart)->format('Y-m-d 00:00:00') :
            DateTime::createFromFormat('Y-m-d H:i:s', $weekAgo)->format('Y-m-d 00:00:00');
        $orders            = self::fetchOrders();
        $previousOrders    = $orders['previousPeriod'];
        $currentOrders     = $orders['currentPeriod'];
        $numPreviousOrders = count($previousOrders);
        $numCurrentOrders  = count($currentOrders);
        $previousRevenue   = 0;
        $currentRevenue    = 0;
        $previousQuantity  = 0;
        $currentQuantity   = 0;
        $previousAoq       = 0;
        $currentAoq        = 0;
        $totalOrdersArr    = [];
        $totalOrdersSet    = [];
        $aovArr            = [];
        $aovSet            = [];
        $aoqArr            = [];
        $aoqSet            = [];
        $datePeriod        = new DatePeriod(
            new DateTime($startDate),
            new DateInterval('P1D'),
            new DateTime($endDate)
        );

        // build the total orders and AOV arrs
        foreach ($datePeriod as $key => $value) {
            $day = $value->format('Y-m-d');

            $totalOrdersArr[$day] = 0;
            $aovArr[$day] = 0;
            $aoqArr[$day] = 0;
        }

        // calculate total revenue and average order quantity for last period
        foreach ($previousOrders as $order) {
            $lineItems = $order->lineItems;
            $previousRevenue += $order->totalPaid;

            foreach ($lineItems as $item) {
                $previousQuantity += $item->qty;
            }
        }

        // add orders to their dates in the total orders arr, and
        // calculate revenue, AOV, and AOQ for current period
        foreach ($currentOrders as $order) {
            $lineItems   = $order->lineItems;
            $dateOrdered = $order->dateOrdered->format('Y-m-d');

            $totalOrdersArr[$dateOrdered] += 1;
            $aovArr[$dateOrdered] += $order->totalPaid;
            $currentRevenue += $order->totalPaid;

            foreach ($lineItems as $item) {
                $currentQuantity += $item->qty;
                $aoqArr[$dateOrdered] += $item->qty;
            }
        }

        $numDaysInSet = count($totalOrdersArr);

        if ($previousQuantity && $numPreviousOrders) {
            $previousAoq = $previousQuantity / $numPreviousOrders;
        }

        if ($currentQuantity) {
            $currentAoq = $currentQuantity / $numCurrentOrders;
        }

        // build the total orders set
        foreach ($totalOrdersArr as $date => $num) {
            $totalOrdersSet[] = $num;
        }

        // build the AOV set
        foreach ($aovArr as $date => $val) {
            $aovSet[] = $val;
        }

        // build the AOQ set
        foreach ($aoqArr as $date => $val) {
            $aoqSet[] = $val;
        }

        $result = [
            'orders' => [
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
                    'percentChange' => $numPreviousOrders ? round((($numCurrentOrders - $numPreviousOrders) / $numPreviousOrders) * 100, 2) : 'INF',
                    'revenue' => $previousRevenue ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2) : 'INF',
                    'series' => $totalOrdersSet
                ],
                // averageOrderValue, averageOrderQuantity
                'averageValue' => [
                    'total' => $numCurrentOrders ? round($currentRevenue / $numCurrentOrders, 2) : 'INF',
                    'percentChange' => $previousRevenue ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2) : 'INF',
                    'series' => $aovSet
                ],
                'averageQuantity' => [
                    'total' => round($currentAoq, 2),
                    'percentChange' => $previousAoq ? round((($currentAoq - $previousAoq) / $previousAoq) * 100, 2) : 'INF',
                    'series' => $aoqSet
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

    protected static function _getOrders($id = null) {
        $orders = self::fetchOrders($id);
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
                'base'          => self::convertCurrency(($order->total - $order->adjustmentSubtotal), $currency),
                'merchTotal'    => self::convertCurrency(($order->total - $order->totalTax - $order->totalShippingCost - $order->totalDiscount), $currency),
                'tax'           => self::convertCurrency($order->totalTax, $currency),
                'shipping'      => self::convertCurrency($order->totalShippingCost, $currency),
                'discount'      => self::convertCurrency($order->totalDiscount, $currency),
                'amountPaid'    => self::convertCurrency($order->totalPaid, $currency),
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

    private static function _getItemsSold() {
        $currency = 'USD';
        $orders   = self::fetchOrders()['currentPeriod'];
        $result   = [];

        foreach ($orders as $order) {
            foreach ($order->lineItems as $item) {
                $variant = $item->purchasable;

                if ($variant) {
                    $product = $variant->product;
                    $result[$variant->sku] = [
                        'id'          => $variant->id,
                        'title'       => $variant->title,
                        'status'      => $variant->status,
                        'sku'         => $variant->sku ?: 'No known SKU',
                        'productId'   => $product->id,
                        'type'        => $product->type->name,
                        'typeHandle'  => $product->type->handle,
                        'lastOrderId' => $result[$variant->sku]['lastOrderId'] ?? 0,
                        'numOrders'   => $result[$variant->sku]['numOrders'] ?? 0,
                        'totalSold'   => $result[$variant->sku]['totalSold'] ?? 0,
                        'sales'       => $result[$variant->sku]['sales'] ?? 0
                    ];

                    if ($result[$variant->sku]['lastOrderId'] !== $order->id) {
                        $result[$variant->sku]['numOrders'] += 1;
                    }

                    $result[$variant->sku]['lastOrderId'] = $order->id;
                    $result[$variant->sku]['totalSold'] += $item->qty;
                    $result[$variant->sku]['sales'] += $item->salePrice * $item->qty;
                }
            }
        }

        foreach ($result as $sku => $item) {
            $result[$sku]['sales'] = self::convertCurrency($result[$sku]['sales'], $currency);
        }

        usort($result, function($a, $b) {
            return $b['totalSold'] <=> $a['totalSold'];
        });

        return $result;
    }
}
