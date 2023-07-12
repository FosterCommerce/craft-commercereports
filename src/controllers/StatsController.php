<?php

/**
 * Commerce Reports Stats Controller
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types=1);

namespace fostercommerce\commercereports\controllers;

use fostercommerce\commercereports\CommerceReports;
use fostercommerce\commercereports\components\Stats;

class StatsController extends Stats
{
    /**
     * Return all stats for a given date range
     *
     * @return \yii\web\Response
     */
    public function getStats(array $orders): \yii\web\Response
    {
        return $this->asJson(CommerceReports::$plugin->stats->getStats($orders));
    }
}
