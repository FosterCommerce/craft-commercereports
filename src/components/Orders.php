<?php

/**
 * Commerce Insights Components Orders Component
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types = 1);

namespace fostercommerce\commerceinsights\components;

use fostercommerce\commerceinsights\CommerceInsights;
use fostercommerce\commerceinsights\interfaces\OrdersInterface;

use Craft;
use craft\web\Controller;
use craft\web\Response;
use yii\web\Response as YiiResponse;

abstract class Orders extends Controller implements OrdersInterface
{
    /**
     * Returns the display name of this class.
     *
     * @return string The display name of this class.
     */
    public static function displayName(): string {
        return Craft::t('commerce-insights', 'Orders');
    }

    /**
     * Returns whether the component should be selectable in component Type selects.
     *
     * @return bool whether the component should be selectable in component Type selects.
     */
    public static function isSelectable(): bool {
        return false;
    }

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
