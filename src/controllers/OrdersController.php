<?php

/**
 * Commerce Insights Components Orders Controller
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types = 1);

namespace fostercommerce\commerceinsights\controllers;

use fostercommerce\commerceinsights\CommerceInsights;
use fostercommerce\commerceinsights\components\Orders;

use craft\web\Response;
use yii\web\Response as YiiResponse;

class OrdersController extends Orders
{
    /**
     * Renders the orders Twig template
     *
     * @return yii\web\Response
     */
    public function actionIndex(): YiiResponse {
        return $this->renderTemplate('commerceinsights/vue/index', [
            'navItem' => 'orders',
        ]);
    }

    /**
     * Return all orders for a given date range
     *
     * @return craft\web\Response
     */
    public function actionGetOrders(): Response {
        return $this->asJson(CommerceInsights::$plugin->orders->getOrders());
    }
}
