<?php
/**
 * Commerce Insights plugin for Craft CMS 3.x
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types = 1);

namespace fostercommerce\commerceinsights\interfaces;

use craft\base\ComponentInterface;
use craft\web\Response;
use yii\web\Response as YiiResponse;

/**
 * @author    Foster Commerce
 * @package   Commerce Insights
 * @since     1.0.0
 */
interface CustomersInterface extends ComponentInterface
{
    /**
     * Renders the customers Twig template
     *
     * @return yii\web\Response
     */
    public function actionIndex(): YiiResponse;

    /**
     * Return all customers for a given date range
     *
     * @return craft\web\Response
     */
    public function actionGetCustomers(): Response;
}
