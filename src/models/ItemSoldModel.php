<?php

/**
 * Commerce Reports Item Sold Model
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types=1);

namespace fostercommerce\commercereports\models;

use Craft;

use craft\base\Model;
use fostercommerce\commercereports\helpers\Helpers;

class ItemSoldModel extends Model
{
    /**
     * Formats the data for orders.
     *
     * @return array
     */
    public static function fromOrders(array $orders): array
    {
        $currency = 'USD';
        $results = [];

        foreach ($orders as $order) {
            foreach ($order->lineItems as $item) {
                $variant = $item->purchasable;

                if ($variant) {
                    $sku = $item['snapshot']['sku'];

                    if (array_key_exists('event', $item['snapshot'])) {
                        $productId = $item['snapshot']['eventId'];
                        $productType = Craft::$app->plugins->getPlugin('events')
                            ->eventTypes->getEventTypeById($item['snapshot']['event']['typeId']);
                    } else {
                        $productId = $item['snapshot']['productId'];
                        $productType = Craft::$app->plugins->getPlugin('commerce')
                            ->productTypes->getProductTypeById($item['snapshot']['product']['typeId']);
                    }

                    $results[$sku] = [
                        'id' => $item['snapshot']['id'],
                        'title' => $item['snapshot']['title'],
                        'status' => $item['snapshot']['status'],
                        'sku' => $item['snapshot']['sku'] ?: 'No known SKU',
                        'productId' => $productId,
                        'type' => $productType->name,
                        'typeHandle' => $productType->handle,
                        'lastOrderId' => $results[$sku]['lastOrderId'] ?? 0,
                        'numOrders' => $results[$sku]['numOrders'] ?? 0,
                        'totalSold' => $results[$sku]['totalSold'] ?? 0,
                        'sales' => $results[$sku]['sales'] ?? 0,
                    ];

                    if ($results[$sku]['lastOrderId'] !== $order->id) {
                        $results[$sku]['numOrders'] += 1;
                    }

                    $results[$sku]['lastOrderId'] = $order->id;
                    $results[$sku]['totalSold'] += $item->qty;
                    $results[$sku]['sales'] += $item->salePrice * $item->qty;
                }
            }
        }

        foreach ($results as $sku => $item) {
            $results[$sku]['sales'] = Helpers::convertCurrency($results[$sku]['sales'], $currency);
        }

        return $results;
    }
}
