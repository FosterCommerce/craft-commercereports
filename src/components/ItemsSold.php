<?php

/**
 * Commerce Insights Items Sold Component
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types = 1);

namespace fostercommerce\commerceinsights\components;

use fostercommerce\commerceinsights\interfaces\ItemsSoldInterface;

use Craft;
use craft\web\Controller;

abstract class ItemsSold extends Controller implements ItemsSoldInterface
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
}
