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
use craft\web\Response;
use yii\web\Response as YiiResponse;

/**
 * @author    Foster Commerce
 * @package   Commerce Reports
 * @since     1.0.0
 */
interface OrdersInterface extends ComponentInterface
{
    /**
     * Renders the orders Twig template
     *
     * @return yii\web\Response
     */
    public function actionIndex(): YiiResponse;

    /**
     * Return all orders for a given date range
     *
     * @return craft\web\Response
     */
    public function actionGetOrders(): Response;
}
