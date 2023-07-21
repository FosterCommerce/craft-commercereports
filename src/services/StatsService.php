<?php

/**
 * Commerce Reports Stats Service
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types=1);

namespace fostercommerce\commercereports\services;

use Craft;

use craft\base\Component;

use craft\commerce\elements\Order;
use DateInterval;
use DatePeriod;

use DateTime;
use fostercommerce\commercereports\helpers\Helpers;

class StatsService extends Component
{
    /**
     * Returns the stats as formatted by the model.
     *
     * @return array
     */
    public function getStats(array $data): array
    {
        $result = [];

        switch ($data['type']) {
            default:
            case 'orders': $result = self::calculateOrdersStats($data);
                break;
            case 'itemsSold': $result = self::calculateItemsSoldStats($data);
                break;
            case 'customers': $result = self::calculateCustomersStats($data);
                break;
        }

        return $result;
    }

    /**
     * Calculate the data for the orderss stats.
     *
     * @param array $data
     *
     * @return array
     */
    private static function calculateOrdersStats(array $data): array
    {
        $orders = $data['data'];
        $previousOrders = $orders['previousPeriod'];
        $currentOrders = $orders['currentPeriod'];
        $numPreviousOrders = count($previousOrders);
        $numCurrentOrders = count($currentOrders);
        $previousRevenue = 0;
        $currentRevenue = 0;
        $previousQuantity = 0;
        $currentQuantity = 0;
        $previousAoq = 0;
        $currentAoq = 0;
        $totalOrdersArr = [];
        $totalOrdersSet = [];
        $aovArr = [];
        $aovSet = [];
        $aoqArr = [];
        $aoqSet = [];
        $datePeriod = new DatePeriod(
            new DateTime($data['start']),
            new DateInterval('P1D'),
            new DateTime($data['end'])
        );

        // build the total orders and AOV arrs
        foreach ($datePeriod as $key => $value) {
            $day = $value->format('Y-m-d');

            $totalOrdersArr[$day] = 0;
            $aovArr[$day] = 0;
            $aoqArr[$day] = 0;
        }

        // Calculate total revenue, average order quantity, and number of
        // customers for the previous period
        foreach ($previousOrders as $order) {
            $lineItems = $order->lineItems;
            $previousRevenue += $order->totalPaid;

            foreach ($lineItems as $item) {
                $previousQuantity += $item->qty;
            }
        }

        // Add orders to their dates in the total orders arr, and calculate
        // revenue, AOV, AOQ, and customers for current period.
        foreach ($currentOrders as $order) {
            $lineItems = $order->lineItems;
            $dateOrdered = $order->dateOrdered->format('Y-m-d');

            $totalOrdersArr[$dateOrdered] += 1;
            $aovArr[$dateOrdered] += $order->totalPaid;
            $currentRevenue += $order->totalPaid;

            foreach ($lineItems as $item) {
                $currentQuantity += $item->qty;
                $aoqArr[$dateOrdered] += $item->qty;
            }
        }

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

        return [
            'orders' => [
                'totalOrders' => [
                    'total' => $numCurrentOrders,
                    // this is based on the new previous period data
                    'percentChange' => $numPreviousOrders ? round((($numCurrentOrders - $numPreviousOrders) / $numPreviousOrders) * 100, 2) : ($numCurrentOrders ? 'INF' : 0),
                    'revenue' => $previousRevenue ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2) : ($currentRevenue ? 'INF' : 0),
                    'series' => $totalOrdersSet,
                ],
                // averageOrderValue, averageOrderQuantity
                'averageValue' => [
                    'total' => $numCurrentOrders ? round($currentRevenue / $numCurrentOrders, 2) : 0,
                    'percentChange' => $previousRevenue ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2) : ($currentRevenue ? 'INF' : 0),
                    'series' => $aovSet,
                ],
                'averageQuantity' => [
                    'total' => round($currentAoq, 2),
                    'percentChange' => $previousAoq ? round((($currentAoq - $previousAoq) / $previousAoq) * 100, 2) : ($currentAoq ? 'INF' : 0),
                    'series' => $aoqSet,
                ],
            ],
        ];
    }

    /**
     * Calculate the data for the items sold stats. There currently are no
     * stats on that page, so this is a placeholder in case it's added later.
     *
     * @param array $data
     *
     * @return array
     */
    private static function calculateItemsSoldStats(array $data): array
    {
        return [];
    }

    /**
     * Calculate the data for the customers stats.
     *
     * @param array $data
     *
     * @return array
     */
    private static function calculateCustomersStats(array $data): array
    {
        $orders = $data['data'];
        $previousOrders = $orders['previousPeriod'];
        $currentOrders = $orders['currentPeriod'];
        $dateRange = Helpers::getDateRangeData();
        $startDate = $dateRange['originalStart'];
        $previousStartDate = $dateRange['previousStart'];
        $previousCustomers = 0;
        $currentCustomers = 0;
        $currentNewCustomers = 0;
        $previousNewCustomers = 0;
        $returningCustomers = 0;
        $previousReturningCustomers = 0;
        $previousCustomersArr = [];
        $currentCustomersArr = [];
        $customerDatesArr = [];
        $newCustomersArr = [];
        $returningCustomersArr = [];
        $totalCustomersSet = [];
        $newCustomersSet = [];
        $returningCustomersSet = [];
        $datePeriod = new DatePeriod(
            new DateTime($startDate),
            new DateInterval('P1D'),
            new DateTime($dateRange['originalEnd'])
        );

        // Build the total customers, new customers, and returning customers arrs
        foreach ($datePeriod as $key => $value) {
            $day = $value->format('Y-m-d');

            $customerDatesArr[$day] = 0;
            $newCustomersArr[$day] = 0;
            $returningCustomersArr[$day] = 0;
        }

        // Calculate the new and returning customers in the previous period
        foreach ($previousOrders as $order) {
            $customerEmail = strtolower($order->email);

            if (!in_array($customerEmail, $previousCustomersArr)) {
                $customerOrderCount = (int)Order::find()->email($customerEmail)->dateOrdered('< ' . $previousStartDate)->count();
                $previousCustomersArr[] = $customerEmail;
                $previousCustomers += 1;

                if ($customerOrderCount === 0) {
                    $previousNewCustomers += 1;
                } else {
                    $previousReturningCustomers += 1;
                }
            }
        }

        // Calculate the new and returning customers in the current period
        foreach ($currentOrders as $order) {
            $customerEmail = strtolower($order->email);
            $dateOrdered = $order->dateOrdered->format('Y-m-d');

            if (!in_array($customerEmail, $currentCustomersArr)) {
                $customerOrderCount = (int)Order::find()->email($customerEmail)->dateOrdered('< ' . $startDate)->count();
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

        return [
            'orders' => [
                'topLocations' => self::getTopLocations($currentOrders),
                'totalCustomers' => [
                    'total' => $currentCustomers,
                    'percentChange' => $previousCustomers ? round((($currentCustomers - $previousCustomers) / $previousCustomers) * 100, 2) : ($currentCustomers ? 'INF' : 0),
                    'series' => $totalCustomersSet,
                ],
                'newCustomers' => [
                    'total' => $currentNewCustomers,
                    'percentChange' => $previousNewCustomers ? round((($currentNewCustomers - $previousNewCustomers) / $previousNewCustomers) * 100, 2) : ($currentNewCustomers ? 'INF' : 0),
                    'series' => $newCustomersSet,
                ],
                'returningCustomers' => [
                    'total' => $returningCustomers,
                    'percentChange' => $previousReturningCustomers ? round((($returningCustomers - $previousReturningCustomers) / $previousReturningCustomers) * 100, 2) : ($returningCustomers ? 'INF' : 0),
                    'series' => $returningCustomersSet,
                ],
            ],
        ];
    }

    /**
     * Calculate the top locations for the customers chart.
     *
     * @param array $orders
     *
     * @return array
     */
    private static function getTopLocations($orders): array
    {
        $topCities = [];
        $topLocations = [];

        foreach ($orders as $order) {
            $address = $order->shippingAddress;

            if (!$address) {
                continue;
            }

            $city = $address->locality ?? '';
            $cityLower = preg_replace('/\s/', '', strtolower($address->locality ?? ''));
            $state = $address->administrativeArea->abbreviation ?? Helpers::zipToUsState($address->postalCode ?? '');
            $country = $address->countryCode ?? '';
            $orderCount = $topCities[$country . $cityLower . $state]['total'] ?? 0;

            $topCities[$country . $cityLower . $state] = [
                'country' => $country,
                'city' => $city,
                'state' => $state,
                'total' => $orderCount + 1,
            ];
        }

        usort($topCities, function($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        $topCities = array_slice($topCities, 0, 7);

        foreach ($topCities as $city) {
            $topLocations[] = [
                'country' => $city['country'],
                'destination' => $city['city'] . ', ' . $city['state'],
                'total' => $city['total'],
            ];
        }

        Craft::warning("TOP LOCATIONS PARSED: ");
        Craft::warning($topLocations);

        return $topLocations;
    }
}
