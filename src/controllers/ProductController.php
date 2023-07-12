<?php

/**
 * Commerce Reports Product Controller
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types=1);

namespace fostercommerce\commercereports\controllers;

use Craft;
use fostercommerce\commercereports\CommerceReports;

use fostercommerce\commercereports\components\Product;

class ProductController extends Product
{
    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
    }

    /**
     * Renders the orders Twig template
     *
     * @return \yii\web\Response
     */
    public function actionIndex(): \yii\web\Response
    {
        return $this->renderTemplate('commercereports/vue/index', [
            'navItem' => 'orders',
            'id' => Craft::$app->request->getQueryParam('id'),
        ]);
    }

    /**
     * Return all orders for a given product inside of a date range
     *
     * @return \yii\web\Response
     */
    public function actionGetProduct(): \yii\web\Response
    {
        return $this->asJson(CommerceReports::$plugin->product->getProduct());
    }
}
