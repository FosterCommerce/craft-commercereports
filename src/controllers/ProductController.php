<?php

/**
 * Commerce Insights Product Controller
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types = 1);

namespace fostercommerce\commerceinsights\controllers;

use fostercommerce\commerceinsights\CommerceInsights;
use fostercommerce\commerceinsights\components\Product;

use Craft;
use craft\web\Response;
use yii\web\Response as YiiResponse;

class ProductController extends Product
{
    public function __construct($id, $module, $config = []) {
        parent::__construct($id, $module, $config);
    }

    /**
     * Renders the orders Twig template
     *
     * @return yii\web\Response
     */
    public function actionIndex(): YiiResponse {
        return $this->renderTemplate('commerceinsights/vue/index', [
            'navItem' => 'orders',
            'id'      => Craft::$app->request->getQueryParam('id')
        ]);
    }

    /**
     * Return all orders for a given product inside of a date range
     *
     * @return craft\web\Response
     */
    public function actionGetProduct(): Response {
        return $this->asJson(CommerceInsights::$plugin->product->getProduct());
    }
}
