<?php

/**
 * Commerce Insights Components Helpers class
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types = 1);

namespace fostercommerce\commerceinsights\helpers;

use DateTime;
use NumberFormatter;
use Money\Money;
use Money\Currency;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;

use Craft;

class Helpers {
    public static function getDateRangeData(): array {
        $result = [];

        // Query params if they exist in the URL
        $startQuery = Craft::$app->request->getQueryParam('startDate') ?? null;
        $endQuery   = Craft::$app->request->getQueryParam('endDate') ?? null;

        // URL decode the query params if they are present
        // TODO: This might override the selected range? Or vice versa. Not implemented yet.
        $result['startQuery'] = $startQuery ? urldecode($startQuery) : null;
        $result['endQuery']   = $endQuery ? urldecode($endQuery) : null;

        // All the formatted dates for the selected range (currently selected period)
        // If none is selected, we default to a week
        $result['today']      = new DateTime(date('Y-m-d 23:59:59'));
        $result['weekAgo']    = $result['today']->modify('-7 day')->format('Y-m-d 00:00:00');
        $result['rangeStart'] = Craft::$app->request->getBodyParam('range_start') ?? null;

        // Either the selected end date, or today
        $result['rangeEnd'] = Craft::$app->request->getBodyParam('range_end') ?? $result['today']->format('Y-m-d 23:59:59');

        // This will be either the selected start date, or a week ago if none is selected
        $result['originalStart'] = $result['rangeStart'] ?
            DateTime::createFromFormat('Y-m-d H:i:s', $result['rangeStart'])->format('Y-m-d 00:00:00') :
            DateTime::createFromFormat('Y-m-d H:i:s', $result['weekAgo'])->format('Y-m-d 00:00:00');

        // This is either the selected end date or today
        $result['originalEnd'] = DateTime::createFromFormat('Y-m-d H:i:s', $result['rangeEnd'])->format('Y-m-d 23:59:59');

        // Set up the new dates based on the full range and the previous period of the same number of days
        $start = DateTime::createFromFormat('Y-m-d H:i:s', $result['originalStart']);
        $end   = DateTime::createFromFormat('Y-m-d H:i:s', $result['originalEnd']);

        // Number of days in the range
        $numDays = $end->diff($start)->format("%r%a");

        // TThe start date for the previous period
        $result['previousStart'] = $start->modify($numDays . ' day')->format('Y-m-d 00:00:00');

        return $result;
    }

    /**
     * Converts a number into a string with the proper currency symbol
     *
     * @param int    $amount   - The amount to convert
     * @param string $currency - The currency
     *
     * @return string
     */
    public static function convertCurrency(float $amount, string $currency) : string {
        $amount          = strpos($amount . '', '.') ? str_replace('.', '', $amount) : $amount . '00';
        $amount          = $amount === '000' ? 0 : preg_replace('/^0/', '', $amount);
        $money           = new Money($amount, new Currency($currency));
        $currencies      = new ISOCurrencies();
        $numberFormatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        $moneyFormatter  = new IntlMoneyFormatter($numberFormatter, $currencies);

        return $moneyFormatter->format($money);
    }

    /**
     * Tells you the state from a zip code.
     *
     * @param string $zipcode - The zip code to convert to a state
     *
     * @return string
     */
    public static function zipToState($zipcode) {
        /* 000 to 999 */
        $zipByState = [
            '--', '--', '--', '--', '--', 'NY', 'PR', 'PR', 'VI', 'PR', 'MA', 'MA', 'MA',
            'MA', 'MA', 'MA', 'MA', 'MA', 'MA', 'MA', 'MA', 'MA', 'MA', 'MA', 'MA', 'MA',
            'MA', 'MA', 'RI', 'RI', 'NH', 'NH', 'NH', 'NH', 'NH', 'NH', 'NH', 'NH', 'NH',
            'ME', 'ME', 'ME', 'ME', 'ME', 'ME', 'ME', 'ME', 'ME', 'ME', 'ME', 'VT', 'VT',
            'VT', 'VT', 'VT', 'MA', 'VT', 'VT', 'VT', 'VT', 'CT', 'CT', 'CT', 'CT', 'CT',
            'CT', 'CT', 'CT', 'CT', 'CT', 'NJ', 'NJ', 'NJ', 'NJ', 'NJ', 'NJ', 'NJ', 'NJ',
            'NJ', 'NJ', 'NJ', 'NJ', 'NJ', 'NJ', 'NJ', 'NJ', 'NJ', 'NJ', 'NJ', 'NJ', 'AE',
            'AE', 'AE', 'AE', 'AE', 'AE', 'AE', 'AE', 'AE', '--', 'NY', 'NY', 'NY', 'NY',
            'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY',
            'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY',
            'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY',
            'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'NY', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA',
            'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA',
            'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA',
            'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', 'PA', '--', 'PA', 'PA',
            'PA', 'PA', 'DE', 'DE', 'DE', 'DC', 'VA', 'DC', 'DC', 'DC', 'DC', 'MD', 'MD',
            'MD', 'MD', 'MD', 'MD', 'MD', '--', 'MD', 'MD', 'MD', 'MD', 'MD', 'MD', 'VA',
            'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA',
            'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA', 'VA',
            'WV', 'WV', 'WV', 'WV', 'WV', 'WV', 'WV', 'WV', 'WV', 'WV', 'WV', 'WV', 'WV',
            'WV', 'WV', 'WV', 'WV', 'WV', 'WV', 'WV', 'WV', 'WV', '--', 'NC', 'NC', 'NC',
            'NC', 'NC', 'NC', 'NC', 'NC', 'NC', 'NC', 'NC', 'NC', 'NC', 'NC', 'NC', 'NC',
            'NC', 'NC', 'NC', 'NC', 'SC', 'SC', 'SC', 'SC', 'SC', 'SC', 'SC', 'SC', 'SC',
            'SC', 'GA', 'GA', 'GA', 'GA', 'GA', 'GA', 'GA', 'GA', 'GA', 'GA', 'GA', 'GA',
            'GA', 'GA', 'GA', 'GA', 'GA', 'GA', 'GA', 'GA', 'FL', 'FL', 'FL', 'FL', 'FL',
            'FL', 'FL', 'FL', 'FL', 'FL', 'FL', 'FL', 'FL', 'FL', 'FL', 'FL', 'FL', 'FL',
            'FL', 'FL', 'AA', 'FL', 'FL', '--', 'FL', '--', 'FL', 'FL', '--', 'FL', 'AL',
            'AL', 'AL', '--', 'AL', 'AL', 'AL', 'AL', 'AL', 'AL', 'AL', 'AL', 'AL', 'AL',
            'AL', 'AL', 'AL', 'AL', 'AL', 'AL', 'TN', 'TN', 'TN', 'TN', 'TN', 'TN', 'TN',
            'TN', 'TN', 'TN', 'TN', 'TN', 'TN', 'TN', 'TN', 'TN', 'MS', 'MS', 'MS', 'MS',
            'MS', 'MS', 'MS', 'MS', 'MS', 'MS', 'MS', 'MS', 'GA', '--', 'KY', 'KY', 'KY',
            'KY', 'KY', 'KY', 'KY', 'KY', 'KY', 'KY', 'KY', 'KY', 'KY', 'KY', 'KY', 'KY',
            'KY', 'KY', 'KY', '--', 'KY', 'KY', 'KY', 'KY', 'KY', 'KY', 'KY', 'KY', '--',
            '--', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH',
            'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH', 'OH',
            'OH', 'OH', 'OH', 'OH', '--', 'IN', 'IN', 'IN', 'IN', 'IN', 'IN', 'IN', 'IN',
            'IN', 'IN', 'IN', 'IN', 'IN', 'IN', 'IN', 'IN', 'IN', 'IN', 'IN', 'IN', 'MI',
            'MI', 'MI', 'MI', 'MI', 'MI', 'MI', 'MI', 'MI', 'MI', 'MI', 'MI', 'MI', 'MI',
            'MI', 'MI', 'MI', 'MI', 'MI', 'MI', 'IA', 'IA', 'IA', 'IA', 'IA', 'IA', 'IA',
            'IA', 'IA', '--', 'IA', 'IA', 'IA', 'IA', 'IA', 'IA', 'IA', '--', '--', '--',
            'IA', 'IA', 'IA', 'IA', 'IA', 'IA', 'IA', 'IA', 'IA', '--', 'WI', 'WI', 'WI',
            '--', 'WI', 'WI', '--', 'WI', 'WI', 'WI', 'WI', 'WI', 'WI', 'WI', 'WI', 'WI',
            'WI', 'WI', 'WI', 'WI', 'MN', 'MN', '--', 'MN', 'MN', 'MN', 'MN', 'MN', 'MN',
            'MN', 'MN', 'MN', 'MN', 'MN', 'MN', 'MN', 'MN', 'MN', '--', 'DC', 'SD', 'SD',
            'SD', 'SD', 'SD', 'SD', 'SD', 'SD', '--', '--', 'ND', 'ND', 'ND', 'ND', 'ND',
            'ND', 'ND', 'ND', 'ND', '--', 'MT', 'MT', 'MT', 'MT', 'MT', 'MT', 'MT', 'MT',
            'MT', 'MT', 'IL', 'IL', 'IL', 'IL', 'IL', 'IL', 'IL', 'IL', 'IL', 'IL', 'IL',
            'IL', 'IL', 'IL', 'IL', 'IL', 'IL', 'IL', 'IL', 'IL', 'IL', '--', 'IL', 'IL',
            'IL', 'IL', 'IL', 'IL', 'IL', 'IL', 'MO', 'MO', '--', 'MO', 'MO', 'MO', 'MO',
            'MO', 'MO', 'MO', 'MO', 'MO', '--', '--', 'MO', 'MO', 'MO', 'MO', 'MO', '--',
            'MO', 'MO', 'MO', 'MO', 'MO', 'MO', 'MO', 'MO', 'MO', '--', 'KS', 'KS', 'KS',
            '--', 'KS', 'KS', 'KS', 'KS', 'KS', 'KS', 'KS', 'KS', 'KS', 'KS', 'KS', 'KS',
            'KS', 'KS', 'KS', 'KS', 'NE', 'NE', '--', 'NE', 'NE', 'NE', 'NE', 'NE', 'NE',
            'NE', 'NE', 'NE', 'NE', 'NE', '--', '--', '--', '--', '--', '--', 'LA', 'LA',
            '--', 'LA', 'LA', 'LA', 'LA', 'LA', 'LA', '--', 'LA', 'LA', 'LA', 'LA', 'LA',
            '--', 'AR', 'AR', 'AR', 'AR', 'AR', 'AR', 'AR', 'AR', 'AR', 'AR', 'AR', 'AR',
            'AR', 'AR', 'OK', 'OK', '--', 'TX', 'OK', 'OK', 'OK', 'OK', 'OK', 'OK', 'OK',
            'OK', '--', 'OK', 'OK', 'OK', 'OK', 'OK', 'OK', 'OK', 'TX', 'TX', 'TX', 'TX',
            'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX',
            'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX',
            'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX',
            'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'TX', 'CO', 'CO', 'CO', 'CO', 'CO', 'CO',
            'CO', 'CO', 'CO', 'CO', 'CO', 'CO', 'CO', 'CO', 'CO', 'CO', 'CO', '--', '--',
            '--', 'WY', 'WY', 'WY', 'WY', 'WY', 'WY', 'WY', 'WY', 'WY', 'WY', 'WY', 'WY',
            'ID', 'ID', 'ID', 'ID', 'ID', 'ID', 'ID', '--', 'UT', 'UT', '--', 'UT', 'UT',
            'UT', 'UT', 'UT', '--', '--', 'AZ', 'AZ', 'AZ', 'AZ', '--', 'AZ', 'AZ', 'AZ',
            '--', 'AZ', 'AZ', '--', '--', 'AZ', 'AZ', 'AZ', '--', '--', '--', '--', 'NM',
            'NM', '--', 'NM', 'NM', 'NM', '--', 'NM', 'NM', 'NM', 'NM', 'NM', 'NM', 'NM',
            'NM', 'NM', '--', '--', '--', '--', 'NV', 'NV', '--', 'NV', 'NV', 'NV', '--',
            'NV', 'NV', '--', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', '--',
            'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA',
            'CA', 'CA', 'CA', 'CA', 'CA', 'CA', '--', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA',
            'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA',
            'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA', 'CA',
            'AP', 'AP', 'AP', 'AP', 'AP', 'HI', 'HI', 'GU', 'OR', 'OR', 'OR', 'OR', 'OR',
            'OR', 'OR', 'OR', 'OR', 'OR', 'WA', 'WA', 'WA', 'WA', 'WA', 'WA', 'WA', '--',
            'WA', 'WA', 'WA', 'WA', 'WA', 'WA', 'WA', 'AK', 'AK', 'AK', 'AK', 'AK'
        ];

        $prefix = substr($zipcode, 0, 3);
        $index = intval($prefix); /* converts prefix to integer */

        return $zipByState[$index];
    }
}
