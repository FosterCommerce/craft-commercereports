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

/**
 * @author    Foster Commerce
 * @package   Commerce Reports
 * @since     1.0.0
 */
interface StatsInterface extends ComponentInterface
{
    /**
     * Return stats for a given date range
     *
     * @return craft\web\Response
     */
    public function getStats(array $orders): Response;
}
