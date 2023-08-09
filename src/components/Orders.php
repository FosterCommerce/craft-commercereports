<?php

/**
 * Commerce Reports Orders Component
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types=1);

namespace fostercommerce\commercereports\components;

use Craft;

use craft\web\Controller;
use fostercommerce\commercereports\interfaces\OrdersInterface;

abstract class Orders extends Controller implements OrdersInterface
{
    /**
     * Returns the display name of this class.
     *
     * @return string The display name of this class.
     */
    public static function displayName(): string
    {
        return Craft::t('commerce-reports', 'Orders');
    }

    /**
     * Returns whether the component should be selectable in component Type selects.
     *
     * @return bool whether the component should be selectable in component Type selects.
     */
    public static function isSelectable(): bool
    {
        return false;
    }
}
