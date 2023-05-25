<?php

/**
 * Commerce Reports Stats Controller
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types = 1);

namespace fostercommerce\commercereports\controllers;

use fostercommerce\commercereports\CommerceReports;
use fostercommerce\commercereports\components\Stats;

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
        return $this->asJson(CommerceReports::$plugin->stats->getStats($orders));
    }
}
