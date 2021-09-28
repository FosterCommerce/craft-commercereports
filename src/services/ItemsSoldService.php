<?php

/**
 * Commerce Insights Orders Service
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types = 1);

namespace fostercommerce\commerceinsights\services;

use fostercommerce\commerceinsights\CommerceInsights;
use fostercommerce\commerceinsights\controllers\StatsController;

use DateTime;

use Craft;
use craft\base\Component;
use fostercommerce\commerceinsights\helpers\Helpers;

class ItemsSoldService extends Component
{
    private $dates;
    // filters
    private $keyword;
    private $orderType;
    private $paymentType;
    // misc
    private $itemsSold = [];

    /**
     * Constructor. Sets up all of the properties for this class based on $_GET and
     * $_POST data, and fetches the orders when the class is intantiated.
     *
     * @return void
     */
    public function __construct($id, $module, $config = []) {
        $this->dates = Helpers::getDateRangeData();

        // Filters that may be set
        $this->keyword     = Craft::$app->request->getBodyParam('keyword');
        $this->orderType   = Craft::$app->request->getBodyParam('orderType');
        $this->paymentType = Craft::$app->request->getBodyParam('paymentType');

        // Go get 'em, tiger
        $this->itemsSold = $this->fetchItemsSold();

        parent::__construct($id, $module, $config);
    }

    /**
     * Fetches the items sold based on the given criteria established in the constructor.
     *
     * @return array
     */
    private function fetchItemsSold(): array {
        return [];
    }

    /**
     * Returns the items sold.
     *
     * @return array
     */
    public function getItemsSold(): array {
       return [];
    }
}
