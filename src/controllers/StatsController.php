<?php

/**
 * Commerce Insights Stats Controller
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types = 1);

namespace fostercommerce\commerceinsights\controllers;

use fostercommerce\commerceinsights\CommerceInsights;
use fostercommerce\commerceinsights\components\Stats;

use craft\web\Response;
use yii\web\Response as YiiResponse;

class StatsController extends Stats
{
    /**
     * Return all stats for a given date range
     *
     * @return craft\web\Response
     */
    public function getStats(array $orders): Response {
        return $this->asJson(CommerceInsights::$plugin->stats->getStats($orders));
    }
}
