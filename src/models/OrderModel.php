<?php

/**
 * Commerce Insights Order Model
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types = 1);

namespace fostercommerce\commerceinsights\models;

use fostercommerce\commerceinsights\helpers\Helpers;

use craft\base\Model;
use craft\commerce\elements\Order;

class OrderModel extends Model
{
    /**
     * Formats the data for orders.
     *
     * @param array $orders - The orders to format
     *
     * @return array
     */
    public static function fromOrders(array $orders): array {
        $currentPeriod = $orders['currentPeriod'] ?? $orders;
        $results = [];

        foreach ($currentPeriod as $idx => $order) {
            $order = Order::find()->id($order['id'])->one();
           
            $lineItems = $order->lineItems;
            $results[] = self::fromOrder($order);

            foreach ($lineItems as $item) {
                $results[$idx]['numItems'] += $item->qty;
            }
        }

        return $results;
    }

    /**
     * Formats the data for a single order.
     *
     * @param object $order - The order to format
     *
     * @return array
     */
    public static function fromOrder(object $order): array {
        $currency = $order->currency;

        return [
            'orderId'       => $order->id,
            'reference'     => $order->friendlyOrderNumber ?? $order->reference,
            'date'          => $order->dateOrdered->format('m/d/Y g:ia'),
            'fullDate'      => $order->dateOrdered->format('l, F j, Y, g:ia'),
            'timeStamp'     => $order->dateOrdered->getTimestamp(),
            'status'        => $order->orderStatus->name,
            'color'         => $order->orderStatus->color,
            'base'          => Helpers::convertCurrency(($order->total - $order->adjustmentSubtotal), $currency),
            'merchTotal'    => Helpers::convertCurrency(($order->total - $order->totalTax - $order->totalShippingCost - $order->totalDiscount), $currency),
            'tax'           => Helpers::convertCurrency($order->totalTax, $currency),
            'shipping'      => Helpers::convertCurrency($order->totalShippingCost, $currency),
            'discount'      => Helpers::convertCurrency($order->totalDiscount, $currency),
            'amountPaid'    => Helpers::convertCurrency($order->totalPaid, $currency),
            'paymentStatus' => ucwords($order->paidStatus),
            'paidColor'     => $order->paidStatus === 'paid' ? 'green' : 'red',
            'email'         => $order->customer->email,
            'billingName'   => ($order->billingAddress->firstName ?? ' ') . ' ' . ($order->billingAddress->lastName ?? ' '),
            'shippingName'  => ($order->shippingAddress->firstName ?? ' ') . ' ' . ($order->shippingAddress->lastName ?? ' '),
            'numItems'      => 0
        ];
    }
}
