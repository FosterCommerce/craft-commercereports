<?php

/**
 * Commerce Insights Orders Model
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types = 1);

namespace fostercommerce\commerceinsights\models;

use fostercommerce\commerceinsights\helpers\Helpers;

use craft\base\Model;

class OrdersModel extends Model
{
    /**
     * Formats the data for orders.
     *
     * @return array
     */
    public function getOrderData(array $orders): array {
        $currentPeriod = $orders['currentPeriod'] ?? [];
        $result = [];

        foreach ($currentPeriod as $idx => $order) {
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

            foreach ($line_items as $item) {
                $result[$idx]['numItems'] += $item->qty;
            }
        }

        return $result;
    }
}
