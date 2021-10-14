<?php

/**
 * Commerce Insights Items Sold Service
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types = 1);

namespace fostercommerce\commerceinsights\services;

use fostercommerce\commerceinsights\CommerceInsights;

use Craft;
use craft\base\Component;
use fostercommerce\commerceinsights\helpers\Helpers;

class ItemsSoldService extends Component
{
    private $itemsSold = [];

    /**
     * Constructor. Fetches the items sold data when the class is instantiated.
     *
     * @return void
     */
    public function __construct($id, $module, $config = []) {
        $this->itemsSold = $this->fetchItemsSold();

        parent::__construct($id, $module, $config);
    }

    /**
     * Fetches the items sold based on the given criteria established in the constructor.
     *
     * @return array
     */
    private function fetchItemsSold(): array {
        $currency = 'USD';
        $orders   = CommerceInsights::$plugin->orders->fetchOrders();
        $orders   = $orders['currentPeriod'];
        $result   = [];

        foreach ($orders as $order) {
            foreach ($order->lineItems as $item) {
                $variant = $item->purchasable;

                if ($variant) {
                    $product = $variant->product;
                    $sku     = $variant->sku;

                    $result[$sku] = [
                        'id'          => $variant->id,
                        'title'       => $variant->title,
                        'status'      => $variant->status,
                        'sku'         => $sku ?: 'No known SKU',
                        'productId'   => $product->id,
                        'type'        => $product->type->name,
                        'typeHandle'  => $product->type->handle,
                        'lastOrderId' => $result[$sku]['lastOrderId'] ?? 0,
                        'numOrders'   => $result[$sku]['numOrders'] ?? 0,
                        'totalSold'   => $result[$sku]['totalSold'] ?? 0,
                        'sales'       => $result[$sku]['sales'] ?? 0
                    ];

                    if ($result[$sku]['lastOrderId'] !== $order->id) {
                        $result[$sku]['numOrders'] += 1;
                    }

                    $result[$sku]['lastOrderId'] = $order->id;
                    $result[$sku]['totalSold'] += $item->qty;
                    $result[$sku]['sales'] += $item->salePrice * $item->qty;
                }
            }
        }

        foreach ($result as $sku => $item) {
            $result[$sku]['sales'] = Helpers::convertCurrency($result[$sku]['sales'], $currency);
        }

        usort($result, function($a, $b) {
            return $b['totalSold'] <=> $a['totalSold'];
        });

        return $result;
    }

    /**
     * Returns the items sold.
     *
     * @return array
     */
    public function getItemsSold(): array {
        return $this->itemsSold;
    }
}
