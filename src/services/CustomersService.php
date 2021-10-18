<?php

/**
 * Commerce Insights Customers Service
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types = 1);

namespace fostercommerce\commerceinsights\services;

use fostercommerce\commerceinsights\CommerceInsights;
use fostercommerce\commerceinsights\helpers\Helpers;
use fostercommerce\commerceinsights\controllers\StatsController;

use DateTime;

use Craft;
use craft\base\Component;
use craft\elements\User;
use craft\commerce\elements\Order;

class CustomersService extends Component
{
    // filters
    private $keyword;
    // misc
    private $customers = [];

    /**
     * Constructor. Sets up all of the properties for this class based on $_GET and
     * $_POST data, and fetches the customers when the class is intantiated.
     *
     * @return void
     */
    public function __construct($id, $module, $config = []) {
        // Filters that may be set
        $this->keyword = Craft::$app->request->getBodyParam('keyword');

        parent::__construct($id, $module, $config);
    }

    /**
     * Fetches the customers based on the given criteria established in the constructor.
     *
     * @return array
     */
    private function fetchCustomers(): array {
        $orders        = CommerceInsights::$plugin->orders->fetchOrders();
        $currentPeriod = $orders['currentPeriod'];
        $today         = new DateTime(date('Y-m-d'));
        $start         = DateTime::createFromFormat('Y-m-d H:i:s', $today->format('Y-m-d 00:00:00'));
        $sixtyDays     = $start->modify('-60 day')->format('Y-m-d 00:00:00');
        $processed     = [];
        $result        = [ 'customers' => [], 'stats' => [] ];
        $statsData     = [
            'type' => 'customers',
            'data' => $orders
        ];

        foreach ($currentPeriod as $order) {
            $line_items = $order->lineItems;
            $email      = strtolower($order->email);

            if(!array_key_exists($email, $processed)) {
                $customerIsActive = (int)Order::find()->email($email)->dateOrdered('>= ' . $sixtyDays)->count();

                $processed[$email] = [
                    'customerId'    => $order->customerId,
                    'ordersCount'   => 1,
                    'aov'           => 0,
                    'amountPaid'    => $order->totalPaid,
                    'email'         => $email,
                    'customer'      => User::find()->email($email)->one(),
                    'billingName'   => ($order->billingAddress->firstName ?? ' ') . ' ' . ($order->billingAddress->lastName ?? ' '),
                    'shippingName'  => ($order->shippingAddress->firstName ?? ' ') . ' ' . ($order->shippingAddress->lastName ?? ' '),
                    'currency'      => $order->currency,
                    'lastPurchase'  => $order->dateOrdered->format('Y-m-d'),
                    'active'        => $customerIsActive
                ];
            } else {
                $processed[$email]['ordersCount'] += 1;
                $processed[$email]['amountPaid']  += $order->totalPaid;

                if($order->datePaid < $processed[$email]['lastPurchase']) {
                    $processed[$email]['lastPurchase'] = $order->dateOrdered->format('Y-m-d');
                }
            }
        }

        foreach ($processed as $email=> $data) {
            $result['customers'][] = [
                'customerId'    => $processed[$email]['customerId'],
                'ordersCount'   => $processed[$email]['ordersCount'],
                'aov'           => Helpers::convertCurrency($data['amountPaid'] / $data['ordersCount'], $data['currency']),
                'amountPaid'    => Helpers::convertCurrency($data['amountPaid'], $data['currency']),
                'email'         => $email,
                'customer'      => $processed[$email]['customer'],
                'currency'      => $processed[$email]['currency'],
                'lastPurchase'  => $processed[$email]['lastPurchase'],
                'billingName'   => $processed[$email]['billingName'],
                'shippingName'  => $processed[$email]['shippingName'],
                'status'        => $processed[$email]['active'] ? 'Active' : 'Inactive'
            ];
        }

        $result['stats'] = CommerceInsights::$plugin->stats->getStats($statsData);

        return $result;
    }

    /**
     * Returns the customers.
     *
     * @return array
     */
    public function getCustomers(): array {
        return $this->fetchCustomers();
    }
}
