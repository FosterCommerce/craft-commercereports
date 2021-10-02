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
use fostercommerce\commerceinsights\controllers\StatsController;

use DateTime;

use Craft;
use craft\base\Component;
use fostercommerce\commerceinsights\helpers\Helpers;

class CustomersService extends Component
{
    private $dates;
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
        $this->dates = Helpers::getDateRangeData();

        // Filters that may be set
        $this->keyword = Craft::$app->request->getBodyParam('keyword');

        // Go get 'em, tiger
        $this->customers = $this->fetchCustomers();

        parent::__construct($id, $module, $config);
    }

    /**
     * Fetches the customers based on the given criteria established in the constructor.
     *
     * @return array
     */
    private function fetchCustomers(): array {
        $result = [];

        return $result;
    }

    /**
     * Returns the customers.
     *
     * @return array
     */
    public function getCustomers(): array {
        $result = [];

        return $result;
    }
}
