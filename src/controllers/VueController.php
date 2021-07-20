<?php

namespace fostercommerce\commerceinsights\controllers;

use craft\web\Controller;
use craft\commerce\elements\Order;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\commerce\helpers\Currency;
use craft\elements\User;
use kuriousagency\commerce\bundles\elements\Bundle;

class VueController extends Controller
{
    private $protocol;
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

        $this->protocol   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $this->start_date = $_POST['range_start'] ?? null;
        $this->end_date   = $_POST['range_end'] ?? null;
    }

    public function actionIndex($view)
    {
        return $this->renderTemplate('commerceinsights/vue/index', [
            'navItem' => $view,
        ]);
    }

    public function actionGetStats()
    {
        return $this->asJson(static::_getStats());
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
        $single   = $_GET['purchasableId'] ?? $id;
        $start    = $this->start_date ? \DateTime::createFromFormat('Y-m-d H:i:s', $this->start_date) : '1970-01-01 00:00:00';
        $end      = $this->end_date ? \DateTime::createFromFormat('Y-m-d H:i:s', $this->end_date) : gmdate('Y-m-d 23:59:59');
        $numDays  = $end->diff($start)->format("%r%a");
        $newStart = $start->modify($numDays . ' day')->format('Y-m-d 00:00:00');
        $newEnd   = $end->format('Y-m-d 23:59:59');
        $orders   = Order::find()->dateOrdered(['and', ">= {$newStart}", "< {$newEnd}"])->distinct()->orderBy('dateOrdered desc');
        $url      = $_SERVER['REQUEST_URI'];
        $path     = basename(parse_url($url, PHP_URL_PATH));
        $result   = [];

        if ($single) {
            $single = Variant::find()->id($single)->one();
            $orders->hasPurchasables([$single]);
        }

        if ($single || $path === 'sales') {
            $orders->orderStatusId('< 4');
        }

        return $orders->all() ?: [];
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
        $this->start_date = $_POST['range_start'] ?? null;
        $this->end_date   = $_POST['range_end']   ?? null;

        $products   = Product::find()->all();
        $orders     = $this->fetchOrders();
        $num_orders = count($orders);
        $currency   = 'USD';
        $result     = [
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
                    'total' => $num_orders,
                    'percentChange' => 8, // this is based on the new previous period data
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
            ],
            'products' => [
                'summary' => [
                    'shipmentsPercentChange' => 0,
                    'ordersPercentChange'    => 0
                ],
                'mostPurchased' => [
                    'types' => []
                ],
                'mostProfitable' => []
            ]
        ];

        foreach ($products as $product) {
            if (!isset($result['products']['mostPurchased']['types'][$product['type']['name']])) {
                $result['products']['mostPurchased']['types'][$product['type']['name']] = 0;
            }

            if ($product['type']['name'] === 'Additions' || $product['type']['name'] === 'Addons') {
                $result['products']['mostProfitable'][$product['defaultSku']] = [
                    'title'  => $product['title'],
                    'values' => [0, 0]
                ];
            }
        }

        foreach ($orders as $order) {
            $line_items = $order->lineItems;

            foreach ($line_items as $item) {
                $purchasable = $item->purchasable;

                if ($purchasable) {
                    $product = $purchasable->product;
                    $result['products']['mostPurchased']['types'][$product->type->name] += $item->qty;

                    if ($product->type->name === 'Additions' || $product->type->name === 'Addons') {
                        $result['products']['mostProfitable'][$product->defaultSku]['values'][0] += $item->qty;
                        $result['products']['mostProfitable'][$product->defaultSku]['values'][1] += $item->salePrice * $item->qty;
                    }
                }
            }
        }

        foreach ($result['products']['mostProfitable'] as $sku => $product) {
            $qty = $product['values'][0];

            if ($qty > 0 && $num_orders > 0) {
                $result['products']['mostProfitable'][$sku]['values'][0] = round($product['values'][0] / $num_orders * 100) . '%';
            } else {
                $result['products']['mostProfitable'][$sku]['values'][0] = '0%';
            }

            $result['products']['mostProfitable'][$sku]['values'][1] = static::convertCurrency($product['values'][1], $currency);
        }

        return $result;
    }

    protected function _getOrders($id = null)
    {
        $this->start_date = $_POST['range_start'] ?? $this->start_date;
        $this->end_date   = $_POST['range_end']   ?? $this->end_date;

        $orders = $this->fetchOrders($id);
        $result = [];

        foreach ($orders as $idx => $order) {
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
