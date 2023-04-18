<?php

/**
 * Commerce Insights Item Sold Model
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types = 1);

namespace fostercommerce\commerceinsights\models;

use fostercommerce\commerceinsights\helpers\Helpers;

use Craft;
use craft\base\Model;

class ItemSoldModel extends Model
{
    /**
     * Formats the data for orders.
     *
     * @return array
     */
    public static function fromOrders(array $orders): array {
        $currency = 'USD';
        $results  = [];

        foreach ($orders as $order) {
            
            foreach ($order->lineItems as $item) {
            
                $variant = $item->purchasable;

                if ($variant) {
                    $product = $variant->product;
                    $sku     = $item['snapshot']['sku'];

                    $results[$sku] = [
                        'id'          => $item['snapshot']['id'],
                        'title'       => $item['snapshot']['title'],
                        'status'      => $item['snapshot']['status'],
                        'sku'         => $item['snapshot']['sku'] ?: 'No known SKU',
                        'productId'   => $product['id'],
                        'type'        => $product->type->name,
                        'typeHandle'  => $product->type->handle,
                        'lastOrderId' => $results[$sku]['lastOrderId'] ?? 0,
                        'numOrders'   => $results[$sku]['numOrders'] ?? 0,
                        'totalSold'   => $results[$sku]['totalSold'] ?? 0,
                        'sales'       => $results[$sku]['sales'] ?? 0
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
