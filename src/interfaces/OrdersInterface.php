<?php
/**
 * Commerce Reports plugin for Craft CMS 3.x
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types = 1);

namespace fostercommerce\commercereports\interfaces;

use craft\base\ComponentInterface;

/**
 * @author    Foster Commerce
 * @package   Commerce Reports
 * @since     1.0.0
 */
interface OrdersInterface
{
    /**
     * Renders the orders Twig template
     *
     * @return \yii\web\Response
     */
    public function actionIndex(): \yii\web\Response;

    /**
     * Return all orders for a given date range
     *
     * @return \yii\web\Response
     */
    public function actionGetOrders(): \yii\web\Response;
}
