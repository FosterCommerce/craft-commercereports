<?php

/**
 * Commerce Insights Stats Component
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types = 1);

namespace fostercommerce\commerceinsights\components;

use fostercommerce\commerceinsights\interfaces\StatsInterface;

use Craft;
use craft\web\Controller;
use craft\web\Response;

abstract class Stats extends Controller implements StatsInterface
{
    /**
     * Returns the display name of this class.
     *
     * @return string The display name of this class.
     */
    public static function displayName(): string {
        return Craft::t('commerce-insights', 'Stats');
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
