<?php

/**
 * Commerce Reports Customers Controller
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types = 1);

namespace fostercommerce\commercereports\controllers;

use fostercommerce\commercereports\CommerceReports;
use fostercommerce\commercereports\components\Customers;

use craft\web\Response;
use yii\web\Response as YiiResponse;

class CustomersController extends Customers
{
    /**
     * Renders the customers Twig template
     *
     * @return \yii\web\Response
     */
    public function actionIndex(): \yii\web\Response {
        return $this->renderTemplate('commercereports/vue/index', [
            'navItem' => 'customers',
        ]);
    }

    /**
     * Return all customers for a given date range
     *
     * @return \yii\web\Response
     */
    public function actionGetCustomers(): \yii\web\Response {
        return $this->asJson(CommerceReports::$plugin->customers->getCustomers());
    }
}
