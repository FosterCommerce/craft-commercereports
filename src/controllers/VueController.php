<?php

namespace fostercommerce\commerceinsights\controllers;

use Craft;
use DateTime;
Use DatePeriod;
Use DateInterval;
use craft\web\Controller;
use craft\commerce\elements\Order;
use craft\commerce\helpers\Currency;
use craft\commerce\elements\Variant;

class VueController extends Controller
{
    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
    }

    public function actionIndex($view)
    {
        return $this->renderTemplate('commerceinsights/vue/index', [
            'navItem' => $view,
        ]);
    }

    public function actionGetStats()
    {
        return $this->asJson(self::_getStats());
    }

    public function actionGetOrders()
    {
        return $this->asJson(self::_getOrders());
    }

    public function actionGetItemsSold() {
        return $this->asJson(self::_getItemsSold());
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
    private static function convertCurrency(float $amount, string $currency, bool $convert = true) : string
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
    private static function fetchOrders($id = null) : array
    {
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

        $result = [
            'previousPeriod' => $orders->dateOrdered(['and', ">= {$newStart}", "< {$currentStart}"])->all(),
            'currentPeriod'  => $orders->dateOrdered(['and', ">= {$currentStart}", "< {$newEnd}"])->all()
        ];

        return $result;
    }

    private static function _getStats()
    {
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

        if ($currentQuantity && $numPreviousOrders) {
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
                    'percentChange' => $numPreviousOrders ? round((($numCurrentOrders - $numPreviousOrders) / $numPreviousOrders) * 100, 2) : 0,
                    'revenue' => $previousRevenue ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2) : 0,
                    'series' => $totalOrdersSet
                ],
                // averageOrderValue, averageOrderQuantity
                'averageValue' => [
                    'total' => $numCurrentOrders ? round($currentRevenue / $numCurrentOrders, 2) : 0,
                    'percentChange' => $previousRevenue ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2) : 0,
                    'series' => $aovSet
                ],
                'averageQuantity' => [
                    'total' => round($currentAoq, 2),
                    'percentChange' => $previousAoq ? round((($currentAoq - $previousAoq) / $previousAoq) * 100, 2) : 0,
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

    private static function _getOrders($id = null)
    {
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
        $result   = self::_initVariantSalesArray();
        $orders   = self::fetchOrders();

        foreach ($orders['currentPeriod'] as $order) {
            foreach ($order->lineItems as $item) {
                $purchasable = $item->purchasable;

                // Skip if line item variant has been deleted
                if (!$purchasable) {
                    continue;
                }

                // is purchasable a bundle?
                if ($purchasable instanceof Bundle) {
                    // get the individual variants from the bundle.
                    $bundleItems = $purchasable->getProducts();
                    $bundleQtys = $purchasable->getQtys();

                    foreach ($bundleItems as $bundleItem) {

                        if (!$resultItem = $result[$bundleItem->sku]) {
                            continue;
                        }

                        if ($resultItem['lastOrderId'] != $order->id) {
                            $resultItem['numOrders'] += 1;
                        }
                        $resultItem['lastOrderId'] = $order->id;

                        // Multiply the qty of the items in this bundle
                        // by the line item qty for cases where a user
                        // buys two sets of the same letters. E.g.: A custom
                        // licence plate frame with the same letters for both.
                        $qty = $bundleQtys[$bundleItem->id] * $item->qty;
                        $resultItem['totalSold'] += $qty;
                        $resultItem['sales'] += $bundleItem->price * $qty;

                        $result[$bundleItem->sku] = $resultItem;
                    }
                } else {
                    if ($result[$purchasable->sku]['lastOrderId'] != $order->id) {
                        $result[$purchasable->sku]['numOrders'] += 1;
                    }
                    $result[$purchasable->sku]['lastOrderId'] = $order->id;
                    $result[$purchasable->sku]['totalSold'] += $item->qty;
                    $result[$purchasable->sku]['sales'] += $item->salePrice * $item->qty;
                }
            }
        }

        foreach ($result as $sku => $item) {
            $result[$sku]['sales'] = self::convertCurrency($result[$sku]['sales'], $currency);
            if($result[$sku]['totalSold'] == 0) {
                unset($result[$sku]);
            }
        }

        usort($result, function($a, $b) {
            return $b['totalSold'] <=> $a['totalSold'];
        });

        return $result;
    }

    private static function _initVariantSalesArray(): array {
        $result = [];

        $variants = Variant::find()->anyStatus()->all();

        foreach($variants as $variant) {
            $product = $variant->product;
            $result[$variant->sku] = [
                'id'          => $variant->id,
                'title'       => $variant->title,
                'status'      => $variant->status,
                'sku'         => $variant->sku ?: 'No known SKU',
                'productId'   => $product->id,
                'type'        => $product->type->name,
                'typeHandle'  => $product->type->handle,
                'totalSold'   => 0,
                'sales'       => 0,
                'numOrders'   => 0,
                'lastOrderId' => 0
            ];
        }

        return $result;
    }
}
