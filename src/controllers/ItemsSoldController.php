<?php

/**
 * Commerce Insights Items Sold Controller
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types = 1);

namespace fostercommerce\commerceinsights\controllers;

use fostercommerce\commerceinsights\CommerceInsights;
use fostercommerce\commerceinsights\components\ItemsSold;

use craft\web\Response;
use yii\web\Response as YiiResponse;

class ItemsSoldController extends ItemsSold
{
    /**
     * Renders the items sold Twig template
     *
     * @return yii\web\Response
     */
    public function actionIndex(): YiiResponse {
        return $this->renderTemplate('commerceinsights/vue/index', [
            'navItem' => 'items-sold',
        ]);
    }

    /**
     * Return all items sold for a given date range
     *
     * @return craft\web\Response
     */
    public function actionGetItemsSold(): Response {
        return $this->asJson(CommerceInsights::$plugin->itemsSold->getItemsSold());
    }
}
