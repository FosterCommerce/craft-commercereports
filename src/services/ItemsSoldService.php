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
use fostercommerce\commerceinsights\models\ItemSoldModel;

use Craft;
use craft\base\Component;

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
        $orders  = CommerceInsights::$plugin->orders->fetchOrders(['withPrevious' => false]);
        $results = ItemSoldModel::fromOrders($orders);

        usort($results, function($a, $b) {
            return $b['totalSold'] <=> $a['totalSold'];
        });

        return $results;
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
