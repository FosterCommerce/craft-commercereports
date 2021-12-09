<?php

/**
 * Commerce Insights Customers Controller
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types = 1);

namespace fostercommerce\commerceinsights\controllers;

use fostercommerce\commerceinsights\CommerceInsights;
use fostercommerce\commerceinsights\components\Customers;

use craft\web\Response;
use yii\web\Response as YiiResponse;

class CustomersController extends Customers
{
    /**
     * Renders the customers Twig template
     *
     * @return yii\web\Response
     */
    public function actionIndex(): YiiResponse {
        return $this->renderTemplate('commerceinsights/vue/index', [
            'navItem' => 'customers',
        ]);
    }

    /**
     * Return all customers for a given date range
     *
     * @return craft\web\Response
     */
    public function actionGetCustomers(): Response {
        return $this->asJson(CommerceInsights::$plugin->customers->getCustomers());
    }
}
