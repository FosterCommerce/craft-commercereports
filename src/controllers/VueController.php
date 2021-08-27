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
use craft\elements\User;
use Money\Money;
use Money\Currency;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;

class VueController extends Controller
{
    private $orders = [];

    public function __construct($id, $module, $config = []) {
        parent::__construct($id, $module, $config);
        $this->orders = self::fetchOrders();
    }

    public function actionIndex($view) {
        return $this->renderTemplate('commerceinsights/vue/index', [
            'navItem' => $view,
        ]);
    }

    public function actionGetStats() {
        return $this->asJson($this->_getStats());
    }

    public function actionGetOrders() {
        return $this->asJson($this->_getOrders());
    }

    public function actionGetItemsSold() {
        return $this->asJson($this->_getItemsSold());
    }

    public function actionGetCustomers() {
        return $this->asJson($this->_getCustomers());
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
    private static function fetchOrders() : array {
        $productId  = Craft::$app->request->getBodyParam('id') ?? null;
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

        if($productId) {
            $product = Variant::find()->id($productId)->one();
            $orders->hasPurchasables([$product]);
        }

        $result = [
            'previousPeriod' => $orders->dateOrdered(['and', ">= {$newStart}", "< {$currentStart}"])->all(),
            'currentPeriod'  => $orders->dateOrdered(['and', ">= {$currentStart}", "< {$newEnd}"])->all()
        ];

        return $result;
    }

    private function _getStats() {
        $today      = new DateTime(date('Y-m-d'));
        $weekAgo    = new DateTime(date('Y-m-d'));
        $weekAgo    = $weekAgo->modify('-7 day')->format('Y-m-d 00:00:00');
        $rangeStart = Craft::$app->request->getBodyParam('range_start');
        $endDate    = Craft::$app->request->getBodyParam('range_end') ?? $today->format('Y-m-d 23:59:59');
        $startDate  = $rangeStart ?
            DateTime::createFromFormat('Y-m-d H:i:s', $rangeStart)->format('Y-m-d 00:00:00') :
            DateTime::createFromFormat('Y-m-d H:i:s', $weekAgo)->format('Y-m-d 00:00:00');
        $end      = DateTime::createFromFormat('Y-m-d H:i:s', $endDate);
        $start    = DateTime::createFromFormat('Y-m-d H:i:s', $startDate);
        $numDays  = $end->diff($start)->format("%r%a");
        // get the previous start date based on what the previous period would be
        $previousStartDate = $start->modify($numDays . ' day')->format('Y-m-d 00:00:00');
        $orders                     = $this->orders;
        $previousOrders             = $orders['previousPeriod'];
        $currentOrders              = $orders['currentPeriod'];
        $numPreviousOrders          = count($previousOrders);
        $numCurrentOrders           = count($currentOrders);
        $previousRevenue            = 0;
        $currentRevenue             = 0;
        $previousQuantity           = 0;
        $currentQuantity            = 0;
        $previousAoq                = 0;
        $currentAoq                 = 0;
        $previousCustomers          = 0;
        $currentCustomers           = 0;
        $currentNewCustomers        = 0;
        $previousNewCustomers       = 0;
        $returningCustomers         = 0;
        $previousReturningCustomers = 0;
        $previousCustomersArr       = [];
        $currentCustomersArr        = [];
        $customerDatesArr           = [];
        $newCustomersArr            = [];
        $returningCustomersArr      = [];
        $totalOrdersArr             = [];
        $totalOrdersSet             = [];
        $aovArr                     = [];
        $aovSet                     = [];
        $aoqArr                     = [];
        $aoqSet                     = [];
        $totalCustomersSet          = [];
        $newCustomersSet            = [];
        $returningCustomersSet      = [];
        $datePeriod = new DatePeriod(
            new DateTime($startDate),
            new DateInterval('P1D'),
            new DateTime($endDate)
        );

        // build the total orders and AOV arrs
        foreach ($datePeriod as $key => $value) {
            $day = $value->format('Y-m-d');

            $totalOrdersArr[$day]        = 0;
            $customerDatesArr[$day]      = 0;
            $newCustomersArr[$day]       = 0;
            $returningCustomersArr[$day] = 0;
            $aovArr[$day]                = 0;
            $aoqArr[$day]                = 0;
        }

        // calculate total revenue, average order quantity, and number of
        // customers for the previous period
        foreach ($previousOrders as $order) {
            $lineItems = $order->lineItems;
            $previousRevenue += $order->totalPaid;

            $customerEmail = strtolower($order->email);

            if(!in_array($customerEmail, $previousCustomersArr)) {
                $customerOrderCount     = (int)Order::find()->email($customerEmail)->dateOrdered('< ' . $previousStartDate)->count();
                $previousCustomersArr[] = $customerEmail;
                $previousCustomers += 1;

                if ($customerOrderCount === 0) {
                    $previousNewCustomers += 1;
                } else {
                    $previousReturningCustomers += 1;
                }
            }

            foreach ($lineItems as $item) {
                $previousQuantity += $item->qty;
            }
        }

        // add orders to their dates in the total orders arr, and calculate
        // revenue, AOV, AOQ, and customers for current period.
        foreach ($currentOrders as $order) {
            $lineItems   = $order->lineItems;
            $dateOrdered = $order->dateOrdered->format('Y-m-d');

            $totalOrdersArr[$dateOrdered] += 1;
            $aovArr[$dateOrdered] += $order->totalPaid;
            $currentRevenue += $order->totalPaid;

            $customerEmail = strtolower($order->email);

            if(!in_array($customerEmail, $currentCustomersArr)) {
                $customerOrderCount    = (int)Order::find()->email($customerEmail)->dateOrdered('< ' . $startDate)->count();
                $currentCustomersArr[] = $customerEmail;
                $currentCustomers += 1;

                $customerDatesArr[$dateOrdered] += 1;

                if ($customerOrderCount === 0) {
                    $newCustomersArr[$dateOrdered] += 1;
                    $currentNewCustomers += 1;
                } else {
                    $returningCustomersArr[$dateOrdered] += 1;
                    $returningCustomers += 1;
                }
            }

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

        // build the total customers set
        foreach ($customerDatesArr as $date => $val) {
            $totalCustomersSet[] = $val;
        }

        // build the new customers set
        foreach ($newCustomersArr as $date => $val) {
            $newCustomersSet[] = $val;
        }

        // build the returning customers set
        foreach ($returningCustomersArr as $date => $val) {
            $returningCustomersSet[] = $val;
        }

        $result = [
            'orders' => [
                // This is in the customers view
                'topLocations' => self::getTopLocations($currentOrders),
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
                    'total' => $currentCustomers,
                    'percentChange' => $previousCustomers ? round((($currentCustomers - $previousCustomers) / $previousCustomers) * 100, 2) : 'INF',
                    'series' => $totalCustomersSet
                ],
                'newCustomers' => [
                    'total' => $currentNewCustomers,
                    'percentChange' => $previousNewCustomers ? round((($currentNewCustomers - $previousNewCustomers) / $previousNewCustomers) * 100, 2) : 'INF',
                    'series' => $newCustomersSet
                ],
                'returningCustomers' => [
                    'total' => $returningCustomers,
                    'percentChange' => $previousReturningCustomers ? round((($returningCustomers - $previousReturningCustomers) / $previousReturningCustomers) * 100, 2) : 'INF',
                    'series' => $returningCustomersSet
                ]
            ]
        ];

        return $result;
    }

    protected function _getOrders() {
        $orders = $this->orders;
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

    private function _getItemsSold() {
        $currency = 'USD';
        $orders   = $this->orders['currentPeriod'];
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

    public function _getCustomers() {
        $orders     = $this->orders['currentPeriod'];
        $processed  = [];
        $result     = [];

        foreach ($orders as $order) {
            $line_items = $order->lineItems;
            $email      = strtolower($order->email);

            if(!array_key_exists($email, $processed)) {
                $processed[$email] = [
                    'ordersCount'   => 1,
                    'itemsQty'      => 0,
                    'aov'           => 0,
                    'amountPaid'    => $order->totalPaid,
                    'email'         => $email,
                    'customer'      => User::find()->email($email)->one(),
                    'billingName'   => ($order->billingAddress->firstName ?? ' ') . ' ' . ($order->billingAddress->lastName ?? ' '),
                    'shippingName'  => ($order->shippingAddress->firstName ?? ' ') . ' ' . ($order->shippingAddress->lastName ?? ' '),
                    'currency'      => $order->currency,
                    'firstPurchase' => $order->dateOrdered->format('Y-m-d'),
                    'lastPurchase'  => $order->dateOrdered->format('Y-m-d')
                ];
            } else {
                $processed[$email]['ordersCount'] += 1;
                $processed[$email]['amountPaid']  += $order->totalPaid;

                if($order->datePaid < $processed[$email]['lastPurchase']) {
                    $processed[$email]['lastPurchase'] = $order->dateOrdered->format('Y-m-d');
                }

                if($order->datePaid > $processed[$email]['firstPurchase']) {
                    $processed[$email]['firstPurchase'] = $order->dateOrdered->format('Y-m-d');
                }
            }

            foreach ($line_items as $item) {
                $processed[$email]['itemsQty'] += $item->qty;
            }
        }

        foreach ($processed as $email=> $data) {
            $result[] = [
                'ordersCount'   => $processed[$email]['ordersCount'],
                'itemsQty'      => $processed[$email]['itemsQty'],
                'aov'           => self::convertCurrency($data['amountPaid'] / $data['ordersCount'], $data['currency']),
                'amountPaid'    => self::convertCurrency($data['amountPaid'], $data['currency']),
                'email'         => $email,
                'customer'      => $processed[$email]['customer'],
                'currency'      => $processed[$email]['currency'],
                'firstPurchase' => $processed[$email]['firstPurchase'],
                'lastPurchase'  => $processed[$email]['lastPurchase'],
                'billingName'   => $processed[$email]['billingName'],
                'shippingName'  => $processed[$email]['shippingName']
            ];
        }

        return $result;
    }

    private static function getTopLocations($orders) {
        $topCities    = [];
        $topLocations = [];

        foreach ($orders as $order) {
            $address     = $order->shippingAddress;
            $city        = $address->city;
            $cityLower   = preg_replace('/\s/', '', strtolower($address->city));
            $state       = $address->state->abbreviation ?? self::zipToState($address->zipCode);
            $country     = $address->country->iso;
            $orderCount  = $topCities[$country . $cityLower . $state]['total'] ?? 0;

            $topCities[$country . $cityLower . $state] = [
                'country' => $country,
                'city'    => $city,
                'state'   => $state,
                'total'   => $orderCount + 1
            ];
        }

        usort($topCities, function($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        $topCities = array_slice($topCities, 0, 7);

        foreach($topCities as $city) {
            $topLocations[] = [
                'country'     => $city['country'],
                'destination' => $city['city'] . ', ' . $city['state'],
                'total'       => $city['total']
            ];
        }

        return $topLocations;
    }

    private static function zipToState($zipcode) {
        /* 000 to 999 */
        $zip_by_state = [
            '--', '--', '--', '--', '--', 'NY', 'PR', 'PR', 'VI', 'PR', 'MA', 'MA', 'MA',
            'MA', 'MA', 'MA', 'MA', 'MA', 'MA', 'MA', 'MA', 'MA', 'MA', 'MA', 'MA', 'MA',
            'MA', 'MA', 'RI', 'RI', 'NH', 'NH', 'NH', 'NH', 'NH', 'NH', 'NH', 'NH', 'NH',
            'ME', 'ME', 'ME', 'ME', 'ME', 'ME', 'ME', 'ME', 'ME', 'ME', 'ME', 'VT', 'VT',
            'VT', 'VT', 'VT', 'MA', 'VT', 'VT', 'VT', 'VT', 'CT', 'CT', 'CT', 'CT', 'CT',
            'CT', 'CT', 'CT', 'CT', 'CT', 'NJ', 'NJ', 'NJ', 'NJ', 'NJ', 'NJ', 'NJ', 'NJ',
            'NJ', 'NJ', 'NJ', 'NJ', 'NJ', 'NJ', 'NJ', 'NJ', 'NJ', 'NJ', 'NJ', 'NJ', 'AE',
            'AE', 'AE', 'AE', 'AE', 'AE', 'AE', 'AE', 'AE', '--', 'NY', 'NY', 'NY', 'NY',
            'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY',
            'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY',
            'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY',
            'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA',
            'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA',
            'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA',
            'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', '--', 'PA', 'PA',
            'PA', 'PA', 'DE', 'DE', 'DE', 'DC', 'VA', 'DC', 'DC', 'DC', 'DC', 'MD', 'MD',
            'MD', 'MD', 'MD', 'MD', 'MD', '--', 'MD', 'MD', 'MD', 'MD', 'MD', 'MD', 'VA',
            'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA',
            'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA',
            'WV', 'WV', 'WV', 'WV', 'WV', 'WV', 'WV', 'WV', 'WV', 'WV', 'WV', 'WV', 'WV',
            'WV', 'WV', 'WV', 'WV', 'WV', 'WV', 'WV', 'WV', 'WV', '--', 'NC', 'NC', 'NC',
            'NC', 'NC', 'NC', 'NC', 'NC', 'NC', 'NC', 'NC', 'NC', 'NC', 'NC', 'NC', 'NC',
            'NC', 'NC', 'NC', 'NC', 'SC', 'SC', 'SC', 'SC', 'SC', 'SC', 'SC', 'SC', 'SC',
            'SC', 'GA', 'GA', 'GA', 'GA', 'GA', 'GA', 'GA', 'GA', 'GA', 'GA', 'GA', 'GA',
            'GA', 'GA', 'GA', 'GA', 'GA', 'GA', 'GA', 'GA', 'FL', 'FL', 'FL', 'FL', 'FL',
            'FL', 'FL', 'FL', 'FL', 'FL', 'FL', 'FL', 'FL', 'FL', 'FL', 'FL', 'FL', 'FL',
            'FL', 'FL', 'AA', 'FL', 'FL', '--', 'FL', '--', 'FL', 'FL', '--', 'FL', 'AL',
            'AL', 'AL', '--', 'AL', 'AL', 'AL', 'AL', 'AL', 'AL', 'AL', 'AL', 'AL', 'AL',
            'AL', 'AL', 'AL', 'AL', 'AL', 'AL', 'TN', 'TN', 'TN', 'TN', 'TN', 'TN', 'TN',
            'TN', 'TN', 'TN', 'TN', 'TN', 'TN', 'TN', 'TN', 'TN', 'MS', 'MS', 'MS', 'MS',
            'MS', 'MS', 'MS', 'MS', 'MS', 'MS', 'MS', 'MS', 'GA', '--', 'KY', 'KY', 'KY',
            'KY', 'KY', 'KY', 'KY', 'KY', 'KY', 'KY', 'KY', 'KY', 'KY', 'KY', 'KY', 'KY',
            'KY', 'KY', 'KY', '--', 'KY', 'KY', 'KY', 'KY', 'KY', 'KY', 'KY', 'KY', '--',
            '--', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH',
            'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH',
            'OH', 'OH', 'OH', 'OH', '--', 'IN', 'IN', 'IN', 'IN', 'IN', 'IN', 'IN', 'IN',
            'IN', 'IN', 'IN', 'IN', 'IN', 'IN', 'IN', 'IN', 'IN', 'IN', 'IN', 'IN', 'MI',
            'MI', 'MI', 'MI', 'MI', 'MI', 'MI', 'MI', 'MI', 'MI', 'MI', 'MI', 'MI', 'MI',
            'MI', 'MI', 'MI', 'MI', 'MI', 'MI', 'IA', 'IA', 'IA', 'IA', 'IA', 'IA', 'IA',
            'IA', 'IA', '--', 'IA', 'IA', 'IA', 'IA', 'IA', 'IA', 'IA', '--', '--', '--',
            'IA', 'IA', 'IA', 'IA', 'IA', 'IA', 'IA', 'IA', 'IA', '--', 'WI', 'WI', 'WI',
            '--', 'WI', 'WI', '--', 'WI', 'WI', 'WI', 'WI', 'WI', 'WI', 'WI', 'WI', 'WI',
            'WI', 'WI', 'WI', 'WI', 'MN', 'MN', '--', 'MN', 'MN', 'MN', 'MN', 'MN', 'MN',
            'MN', 'MN', 'MN', 'MN', 'MN', 'MN', 'MN', 'MN', 'MN', '--', 'DC', 'SD', 'SD',
            'SD', 'SD', 'SD', 'SD', 'SD', 'SD', '--', '--', 'ND', 'ND', 'ND', 'ND', 'ND',
            'ND', 'ND', 'ND', 'ND', '--', 'MT', 'MT', 'MT', 'MT', 'MT', 'MT', 'MT', 'MT',
            'MT', 'MT', 'IL', 'IL', 'IL', 'IL', 'IL', 'IL', 'IL', 'IL', 'IL', 'IL', 'IL',
            'IL', 'IL', 'IL', 'IL', 'IL', 'IL', 'IL', 'IL', 'IL', 'IL', '--', 'IL', 'IL',
            'IL', 'IL', 'IL', 'IL', 'IL', 'IL', 'MO', 'MO', '--', 'MO', 'MO', 'MO', 'MO',
            'MO', 'MO', 'MO', 'MO', 'MO', '--', '--', 'MO', 'MO', 'MO', 'MO', 'MO', '--',
            'MO', 'MO', 'MO', 'MO', 'MO', 'MO', 'MO', 'MO', 'MO', '--', 'KS', 'KS', 'KS',
            '--', 'KS', 'KS', 'KS', 'KS', 'KS', 'KS', 'KS', 'KS', 'KS', 'KS', 'KS', 'KS',
            'KS', 'KS', 'KS', 'KS', 'NE', 'NE', '--', 'NE', 'NE', 'NE', 'NE', 'NE', 'NE',
            'NE', 'NE', 'NE', 'NE', 'NE', '--', '--', '--', '--', '--', '--', 'LA', 'LA',
            '--', 'LA', 'LA', 'LA', 'LA', 'LA', 'LA', '--', 'LA', 'LA', 'LA', 'LA', 'LA',
            '--', 'AR', 'AR', 'AR', 'AR', 'AR', 'AR', 'AR', 'AR', 'AR', 'AR', 'AR', 'AR',
            'AR', 'AR', 'OK', 'OK', '--', 'TX', 'OK', 'OK', 'OK', 'OK', 'OK', 'OK', 'OK',
            'OK', '--', 'OK', 'OK', 'OK', 'OK', 'OK', 'OK', 'OK', 'TX', 'TX', 'TX', 'TX',
            'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX',
            'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX',
            'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX',
            'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'CO', 'CO', 'CO', 'CO', 'CO', 'CO',
            'CO', 'CO', 'CO', 'CO', 'CO', 'CO', 'CO', 'CO', 'CO', 'CO', 'CO', '--', '--',
            '--', 'WY', 'WY', 'WY', 'WY', 'WY', 'WY', 'WY', 'WY', 'WY', 'WY', 'WY', 'WY',
            'ID', 'ID', 'ID', 'ID', 'ID', 'ID', 'ID', '--', 'UT', 'UT', '--', 'UT', 'UT',
            'UT', 'UT', 'UT', '--', '--', 'AZ', 'AZ', 'AZ', 'AZ', '--', 'AZ', 'AZ', 'AZ',
            '--', 'AZ', 'AZ', '--', '--', 'AZ', 'AZ', 'AZ', '--', '--', '--', '--', 'NM',
            'NM', '--', 'NM', 'NM', 'NM', '--', 'NM', 'NM', 'NM', 'NM', 'NM', 'NM', 'NM',
            'NM', 'NM', '--', '--', '--', '--', 'NV', 'NV', '--', 'NV', 'NV', 'NV', '--',
            'NV', 'NV', '--', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', '--',
            'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA',
            'CA', 'CA', 'CA', 'CA', 'CA', 'CA', '--', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA',
            'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA',
            'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA',
            'AP', 'AP', 'AP', 'AP', 'AP', 'HI', 'HI', 'GU', 'OR', 'OR', 'OR', 'OR', 'OR',
            'OR', 'OR', 'OR', 'OR', 'OR', 'WA', 'WA', 'WA', 'WA', 'WA', 'WA', 'WA', '--',
            'WA', 'WA', 'WA', 'WA', 'WA', 'WA', 'WA', 'AK', 'AK', 'AK', 'AK', 'AK'
        ];

        $prefix = substr($zipcode, 0, 3);
        $index = intval($prefix); /* converts prefix to integer */

        return $zip_by_state[$index];
    }
}
