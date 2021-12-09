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
interface ProductInterface extends ComponentInterface
{
    /**
     * Renders the product Twig template
     *
     * @return yii\web\Response
     */
    public function actionIndex(): YiiResponse;

    /**
     * Return all orders for a given product inside of a date range
     *
     * @return craft\web\Response
     */
    public function actionGetProduct(): Response;
}
