<?php

/**
 * Commerce Reports Items Sold Controller
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types=1);

namespace fostercommerce\commercereports\controllers;

use fostercommerce\commercereports\CommerceReports;
use fostercommerce\commercereports\components\ItemsSold;

class ItemsSoldController extends ItemsSold
{
    /**
     * Renders the items sold Twig template
     *
     * @return \yii\web\Response
     */
    public function actionIndex(): \yii\web\Response
    {
        return $this->renderTemplate('commercereports/vue/index', [
            'navItem' => 'items-sold',
        ]);
    }

    /**
     * Return all items sold for a given date range
     *
     * @return \yii\web\Response
     */
    public function actionGetItemsSold(): \yii\web\Response
    {
        return $this->asJson(CommerceReports::$plugin->itemsSold->getItemsSold());
    }
}
