<?php

/**
 * Commerce Reports Orders Controller
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types=1);

namespace fostercommerce\commercereports\controllers;

use fostercommerce\commercereports\CommerceReports;
use fostercommerce\commercereports\components\Orders;

class OrdersController extends Orders
{
    /**
     * Renders the orders Twig template
     *
     * @return \yii\web\Response
     */
    public function actionIndex(): \yii\web\Response
    {
        return $this->renderTemplate('commercereports/vue/index', [
            'navItem' => 'orders',
        ]);
    }

    /**
     * Return all orders for a given date range
     *
     * @return \yii\web\Response
     */
    public function actionGetOrders(): \yii\web\Response
    {
        return $this->asJson(CommerceReports::$plugin->orders->getOrders());
    }
}
