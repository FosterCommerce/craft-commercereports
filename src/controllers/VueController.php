<?php

namespace fostercommerce\commerceinsights\controllers;

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

        $today          = new \DateTime(date('Y-m-d'));
        $weekAgo        = new \DateTime(date('Y-m-d'));
        $weekAgo        = $weekAgo->modify('-7 day')->format('Y-m-d 00:00:00');
        $rangeStart     = \Craft::$app->request->getBodyParam('range_start');
        $this->end_date = \Craft::$app->request->getBodyParam('range_end') ?? $today->format('Y-m-d 23:59:59');

        $this->start_date = $rangeStart ?
            \DateTime::createFromFormat('Y-m-d H:i:s', $rangeStart)->format('Y-m-d 00:00:00') :
            \DateTime::createFromFormat('Y-m-d H:i:s', $weekAgo)->format('Y-m-d 00:00:00');
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
        $this->orders = $this->fetchOrders();
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
        $single       = \Craft::$app->request->getQueryParam('purchasableId') ?? $id;
        $currentStart = \DateTime::createFromFormat('Y-m-d H:i:s', $this->start_date)->format('Y-m-d 00:00:00');
        $start        = \DateTime::createFromFormat('Y-m-d H:i:s', $this->start_date);
        $end          = \DateTime::createFromFormat('Y-m-d H:i:s', $this->end_date);
        // possible filters
        $keyword      = \Craft::$app->request->getBodyParam('keyword');
        $orderType    = \Craft::$app->request->getBodyParam('orderType');
        $paymentType  = \Craft::$app->request->getBodyParam('paymentType');
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

        if ($keyword) {
            // TODO: filter results by keyword
        }

        if ($orderType) {
            $orders->orderStatus(strtolower($orderType));
        }

        if ($paymentType) {
            $orders->where(['paidStatus' => strtolower($paymentType)]);
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
        $datePeriod        = new \DatePeriod(
            new \DateTime($this->start_date),
            new \DateInterval('P1D'),
            new \DateTime($this->end_date)
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

        // if 12 weeks or more is selected, chunk the stats by the
        // largest prime factor of days so the series doesn't get too large
        if ($numDaysInSet > 83) {
            $largestPrime     = self::largestPrime($numDaysInSet);
            $chunkedOrdersArr = array_chunk($totalOrdersArr, $largestPrime);
            $chunkedAovArr    = array_chunk($aovArr, $largestPrime);
            $chunkedAoqArr    = array_chunk($aoqArr, $largestPrime);

            // build the total orders set
            foreach ($chunkedOrdersArr as $key => $arr) {
                $chunkTotal = 0;

                foreach ($arr as $num) {
                    $chunkTotal += $num;
                }

                $totalOrdersSet[] = $chunkTotal;
            }

            // build the AOV set
            foreach ($chunkedAovArr as $key => $arr) {
                $chunkTotal = 0;

                foreach ($arr as $num) {
                    $chunkTotal += $num;
                }

                $aovSet[] = $chunkTotal;
            }

            // build the AOQ set
            foreach ($chunkedAoqArr as $key => $arr) {
                $chunkTotal = 0;

                foreach ($arr as $num) {
                    $chunkTotal += $num;
                }

                $aoqSet[] = $chunkTotal;
            }
        } else { // selected range is less than 12 weeks
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

    private static function largestPrime($num)
    {
        $largestPrime = -1;

        //check divisibility by 2 to know if 2 might be the largest prime factor
        while ($num % 2 == 0) {
            $largestPrime = 2;
            $num >>= 1;
        }

        //Prime factors are always odd, so skipping even numbers
        for ($flag = 3; $flag <= sqrt($num); $flag += 2) {
            while ($num % $flag == 0) {
                $largestPrime = $flag;
                $num = $num / $flag;
            }
        }

        //see if the $number is a prime which is greater than 2
        if ($num > 2) {
            $largestPrime = $num;
        }

        return $largestPrime;
    }
}
