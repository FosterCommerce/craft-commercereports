<?php

/**
 * Commerce Reports Customers Service
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types=1);

namespace fostercommerce\commercereports\services;

use craft\base\Component;
use craft\commerce\elements\Order;

use craft\elements\User;

use Craft;
use DateTime;
use fostercommerce\commercereports\CommerceReports;
use fostercommerce\commercereports\helpers\Helpers;

class CustomersService extends Component
{
    /**
     * Constructor. Sets up all of the properties for this class based on $_GET and
     * $_POST data, and fetches the customers when the class is intantiated.
     *
     * @return void
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
    }

    /**
     * Fetches the customers based on the given criteria established in the constructor.
     *
     * @return array
     */
    private function fetchCustomers(): array
    {

        $orders = CommerceReports::$plugin->orders->fetchOrders(['withAddresses' => true]);

        $currentPeriod = $orders['currentPeriod'];

        $processed = [];
        $result = [ 'customers' => [], 'stats' => [] ];
        $statsData = [
            'type' => 'customers',
            'data' => $orders,
        ];

        foreach ($currentPeriod as $order) {

            $lineItems = $order['lineItems'];
            $email = strtolower($order['customer']['email']);

            if (!array_key_exists($email, $processed)) {

                $processed[$email] = [
                    'customerId' => $order['customerId'],
                    'ordersCount' => 1,
                    'aov' => 0,
                    'amountPaid' => (float)$order['totalPaid'],
                    'email' => $email,
                    'customer' => $order['customer'],
                    'billingName' => ($order['billingAddress']['firstName'] ?? ' ') . ' ' . ($order['billingAddress']['lastName'] ?? ' '),
                    'shippingName' => ($order['shippingAddress']['firstName'] ?? ' ') . ' ' . ($order['shippingAddress']['lastName'] ?? ' '),
                    'currency' => $order['currency'],
                    'lastPurchase' => $order['dateOrdered']->format('Y-m-d'),
                    'active' => $order['customer']['activeOrderCount'],
                ];

            } else {

                $processed[$email]['ordersCount'] += 1;
                $processed[$email]['amountPaid'] += (float)$order['totalPaid'];

                if ($order['datePaid'] < $processed[$email]['lastPurchase']) {
                    $processed[$email]['lastPurchase'] = $order['dateOrdered']->format('Y-m-d');
                }

            }

        }

        foreach ($processed as $email => $data) {

            $result['customers'][] = [
                'customerId' => $processed[$email]['customerId'],
                'ordersCount' => $processed[$email]['ordersCount'],
                'aov' => Helpers::convertCurrency(($data['amountPaid'] / $data['ordersCount']), $data['currency']),
                'amountPaid' => Helpers::convertCurrency($data['amountPaid'], $data['currency']),
                'email' => $email,
                'customer' => $processed[$email]['customer'],
                'currency' => $processed[$email]['currency'],
                'lastPurchase' => $processed[$email]['lastPurchase'],
                'billingName' => $processed[$email]['billingName'],
                'shippingName' => $processed[$email]['shippingName'],
                'status' => $processed[$email]['active'] ? 'Active' : 'Inactive',
            ];

        }

        $result['stats'] = CommerceReports::$plugin->stats->getStats($statsData);

        return $result;

    }

    /**
     * Returns the customers.
     *
     * @return array
     */
    public function getCustomers(): array
    {
        return $this->fetchCustomers();
    }
}
