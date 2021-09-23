<?php

/**
 * Commerce Insights Components Stats Service
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types = 1);

namespace fostercommerce\commerceinsights\services;

use fostercommerce\commerceinsights\helpers\Helpers;

use DateTime;
use DatePeriod;
use DateInterval;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;

class StatsService extends Component
{
    /**
     * Returns the stats as formatted by the model.
     *
     * @return array
     */
    public function getStats(array $data): array {
        $orders                     = $data['orders'];
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
        /*$previousCustomers          = 0;
        $currentCustomers           = 0;
        $currentNewCustomers        = 0;
        $previousNewCustomers       = 0;
        $returningCustomers         = 0;
        $previousReturningCustomers = 0;
        $previousCustomersArr       = [];
        $currentCustomersArr        = [];
        $customerDatesArr           = [];
        $newCustomersArr            = [];
        $returningCustomersArr      = [];*/
        $totalOrdersArr             = [];
        $totalOrdersSet             = [];
        $aovArr                     = [];
        $aovSet                     = [];
        $aoqArr                     = [];
        $aoqSet                     = [];
        /*$totalCustomersSet          = [];
        $newCustomersSet            = [];
        $returningCustomersSet      = [];*/
        $datePeriod                 = new DatePeriod(
            new DateTime($data['start']),
            new DateInterval('P1D'),
            new DateTime($data['end'])
        );

        // build the total orders and AOV arrs
        foreach ($datePeriod as $key => $value) {
            $day = $value->format('Y-m-d');

            $totalOrdersArr[$day]        = 0;
            /*$customerDatesArr[$day]      = 0;
            $newCustomersArr[$day]       = 0;
            $returningCustomersArr[$day] = 0;*/
            $aovArr[$day]                = 0;
            $aoqArr[$day]                = 0;
        }

        // Calculate total revenue, average order quantity, and number of
        // customers for the previous period
        foreach ($previousOrders as $order) {
            $lineItems = $order->lineItems;
            $previousRevenue += $order->totalPaid;

            /*$customerEmail = strtolower($order->email);

            if(!in_array($customerEmail, $previousCustomersArr)) {
                $customerOrderCount = (int)Order::find()->email($customerEmail)
                    ->dateOrdered('< ' . $data['previousStart'])
                    ->count();
                $previousCustomersArr[] = $customerEmail;
                $previousCustomers += 1;

                if ($customerOrderCount === 0) {
                    $previousNewCustomers += 1;
                } else {
                    $previousReturningCustomers += 1;
                }
            }*/

            foreach ($lineItems as $item) {
                $previousQuantity += $item->qty;
            }
        }

        // Add orders to their dates in the total orders arr, and calculate
        // revenue, AOV, AOQ, and customers for current period.
        foreach ($currentOrders as $order) {
            $lineItems   = $order->lineItems;
            $dateOrdered = $order->dateOrdered->format('Y-m-d');

            $totalOrdersArr[$dateOrdered] += 1;
            $aovArr[$dateOrdered] += $order->totalPaid;
            $currentRevenue += $order->totalPaid;

            /*$customerEmail = strtolower($order->email);

            if(!in_array($customerEmail, $currentCustomersArr)) {
                $customerOrderCount    = (int)Order::find()->email($customerEmail)
                    ->dateOrdered('< ' . $data['start'])
                    ->count();
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
            }*/

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
        /*foreach ($customerDatesArr as $date => $val) {
            $totalCustomersSet[] = $val;
        }

        // build the new customers set
        foreach ($newCustomersArr as $date => $val) {
            $newCustomersSet[] = $val;
        }

        // build the returning customers set
        foreach ($returningCustomersArr as $date => $val) {
            $returningCustomersSet[] = $val;
        }*/

        return [
            'orders' => [
                // This is in the customers view
                'topLocations' => self::getTopLocations($currentOrders),
                'totalOrders' => [
                    'total' => $numCurrentOrders,
                    // this is based on the new previous period data
                    'percentChange' => $numPreviousOrders ? round((($numCurrentOrders - $numPreviousOrders) / $numPreviousOrders) * 100, 2) : ($numCurrentOrders ? 'INF' : 0),
                    'revenue' => $previousRevenue ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2) : ($currentRevenue ? 'INF' : 0),
                    'series' => $totalOrdersSet
                ],
                // averageOrderValue, averageOrderQuantity
                'averageValue' => [
                    'total' => $numCurrentOrders ? round($currentRevenue / $numCurrentOrders, 2) : ($numCurrentOrders ? 'INF' : 0),
                    'percentChange' => $previousRevenue ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2) : ($currentRevenue ? 'INF' : 0),
                    'series' => $aovSet
                ],
                'averageQuantity' => [
                    'total' => round($currentAoq, 2),
                    'percentChange' => $previousAoq ? round((($currentAoq - $previousAoq) / $previousAoq) * 100, 2) : ($currentAoq ? 'INF' : 0),
                    'series' => $aoqSet
                ],
                /*'totalCustomers' => [
                    'total' => $currentCustomers,
                    'percentChange' => $previousCustomers ? round((($currentCustomers - $previousCustomers) / $previousCustomers) * 100, 2) : ($currentCustomers ? 'INF' : 0),
                    'series' => $totalCustomersSet
                ],
                'newCustomers' => [
                    'total' => $currentNewCustomers,
                    'percentChange' => $previousNewCustomers ? round((($currentNewCustomers - $previousNewCustomers) / $previousNewCustomers) * 100, 2) : ($currentNewCustomers ? 'INF' : 0),
                    'series' => $newCustomersSet
                ],
                'returningCustomers' => [
                    'total' => $returningCustomers,
                    'percentChange' => $previousReturningCustomers ? round((($returningCustomers - $previousReturningCustomers) / $previousReturningCustomers) * 100, 2) : ($returningCustomers ? 'INF' : 0),
                    'series' => $returningCustomersSet
                ]*/
            ]
        ];
    }

    private static function getTopLocations($orders) {
        $topCities    = [];
        $topLocations = [];

        foreach ($orders as $order) {
            $address     = $order->shippingAddress;
            $city        = $address->city;
            $cityLower   = preg_replace('/\s/', '', strtolower($address->city));
            $state       = $address->state->abbreviation ?? Helpers::zipToState($address->zipCode);
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
}
