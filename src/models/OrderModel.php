<?php

/**
 * Commerce Reports Order Model
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types=1);

namespace fostercommerce\commercereports\models;

use Craft;
use craft\base\Model;

use fostercommerce\commercereports\helpers\Helpers;

use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;

class OrderModel extends Model
{

    public static function toOrders(array $orders): array 
    {

        $parsed = [];
        foreach ($orders as $order) {

            $lineItems = [];
            foreach (json_decode($order['lineItems'], true) as $lineItem) {

                $lineItemObj = new LineItem();
                $lineItemObj->setAttributes($lineItem, false);

                $lineItems[] = $lineItemObj;
            
            }

            $order['lineItems'] = $lineItems;

            $orderObj = new Order();
            $orderObj->setAttributes($order, false);

            $parsed[] = $orderObj;

        }

        return $parsed;

    }

    public static function calculateAdjustmentSubtotal(array $order) : float {

        $value = 0;

        foreach ($order['adjustments'] as $adjustment) {

            if (!$adjustment['included']) {

                $value += $adjustment['amount'];

            }

        }

        return (float)$value;

    }

   /**
     * Formats the data for orders.
     *
     * @param array $orders - The orders to format
     *
     * @return array
     */
    public static function fromArrayedOrders(array $orders): array
    {
        $currentPeriod = $orders['currentPeriod'] ?? $orders;
        $results = [];



        foreach ($currentPeriod as $idx => $order) {

            $results[] = self::fromArrayedOrder($order);

            foreach ($order['lineItems'] as $item) {
                $results[$idx]['numItems'] += $item['qty'];
            }

        }

        return $results;
    }

    public static function normalizeArrayedOrders(array $orders): array {

        $parsed = [];
        foreach ($orders as $order) {

            $order['lineItems'] = json_decode($order['lineItems'], true);
            $order['adjustments'] = json_decode($order['adjustments'], true);
            $order['orderStatus'] = json_decode($order['orderStatus'], true);
            $order['shippingAddress'] = json_decode($order['shippingAddress'] ?? '', true);
            $order['billingAddress'] = json_decode($order['billingAddress'] ?? '', true);
            $order['customer'] = json_decode($order['customer'] ?? '', true);
            $order['dateOrdered'] = new \DateTime($order['dateOrdered']);
            $order['adjustmentSubtotal'] = self::calculateAdjustmentSubtotal($order);    

            $parsed[] = $order;

        }

        return $parsed;

    }

    public static function fromArrayedOrder(array $order): array 
    {

        $currency = $order['currency'];

        return [
            'orderId' => $order['id'],
            'reference' => $order['reference'],
            'date' => $order['dateOrdered']->format('m/d/Y g:ia'),
            'fullDate' => $order['dateOrdered']->format('l, F j, Y, g:ia'),
            'timeStamp' => $order['dateOrdered']->getTimestamp(),
            'status' => $order['orderStatus']['name'],
            'color' => $order['orderStatus']['color'],
            'base' => Helpers::convertCurrency(((float)$order['total'] - $order['adjustmentSubtotal']), $currency),
            'merchTotal' => Helpers::convertCurrency(((float)$order['total'] - (float)$order['totalTax'] - $order['totalShippingCost'] - $order['totalDiscount']), $currency),
            'tax' => Helpers::convertCurrency((float)$order['totalTax'], $currency),
            'shipping' => Helpers::convertCurrency((float)$order['totalShippingCost'], $currency),
            'discount' => Helpers::convertCurrency((float)$order['totalDiscount'], $currency),
            'amountPaid' => Helpers::convertCurrency((float)$order['totalPaid'], $currency),
            'paymentStatus' => ucwords($order['paidStatus']),
            'paidColor' => $order['paidStatus'] === 'paid' ? 'green' : 'red',
            // 'email' => $order['customer']['email'],
            // 'billingName' => ($order->billingAddress->firstName ?? ' ') . ' ' . ($order->billingAddress->lastName ?? ' '),
            // 'shippingName' => ($order->shippingAddress->firstName ?? ' ') . ' ' . ($order->shippingAddress->lastName ?? ' '),
            'numItems' => 0,
        ];

    }

}
