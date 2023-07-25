<?php

/**
 * Commerce Reports Orders Service
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2021 Foster Commerce
 */

declare(strict_types=1);

namespace fostercommerce\commercereports\services;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;

use craft\commerce\elements\Variant;
use fostercommerce\commercereports\CommerceReports;
use fostercommerce\commercereports\helpers\Helpers;
use fostercommerce\commercereports\models\OrderModel;

use craft\db\Query;

class OrdersService extends Component
{
    protected $dates;
    // filters
    protected $keyword;
    protected $orderType;
    protected $paymentType;

    /**
     * Constructor. Sets up all of the properties for this class based on $_GET and
     * $_POST data, and fetches the orders when the class is intantiated.
     *
     * @return void
     */
    public function __construct($config = [])
    {
        $this->dates = Helpers::getDateRangeData();

        parent::__construct($config);
    }

    /**
     * Fetches the orders based on the given criteria established in the constructor.
     *
     * @param array $opts - [
     *   int  'id'           - Product ID if you want to fetch all the orders for a product
     *   bool 'withPrevious' - Whether or not you want to fetch the previous period's orders
     * ]
     *
     * @return array
     */
    public function fetchOrders(array $opts = []): array
    {

        $productId = $opts['productId'] ?? null;
        $withPrevious = $opts['withPrevious'] ?? true;
        $withAddresses = $opts['withAddresses'] ?? false;

        $today = \DateTime::createFromFormat('Y-m-d H:i:s', (new \DateTime(date('Y-m-d')))->format('Y-m-d 00:00:00'));
        $sixtyDays = $today->modify('-60 day')->format('Y-m-d 00:00:00');
        
        $result = [];

        $baseOrdersQuery = (new Query())
            ->from('{{%elements}} elements')
            ->select([
                'elementsId' => 'elements.id',
                'elementsSiteId' => 'elements_sites.id',
                'contentId' => 'content.id'
            ])
            ->innerJoin(
                '{{%commerce_orders}} commerce_orders', '[[elements.id]] = [[commerce_orders.id]]'
            )
            ->innerJoin(
                '{{%content}} content', '[[elements.id]] = [[content.elementId]]'
            )
            ->innerJoin(
                '{{%elements_sites}} elements_sites', '[[elements.id]] = [[elements_sites.elementId]]'
            )
            ->where([
                '=', "[[elements.archived]]", FALSE
            ])
            ->andWhere([
                'is', '[[elements.dateDeleted]]', NULL
            ])
            ->andWhere([
                'is', '[[elements.draftId]]', NULL
            ])
            ->andWhere([
                'is', '[[elements.revisionId]]', NULL
            ])
            ->orderBy('[[commerce_orders.dateOrdered]] DESC');

        $currentOrdersQuery = (clone $baseOrdersQuery)
            ->andWhere([
                '>=', "[[commerce_orders.dateOrdered]]", $this->dates['originalStart']
            ])
            ->andWhere([
                '<', "[[commerce_orders.dateOrdered]]", $this->dates['originalEnd']
            ]);

        $previousOrdersQuery = (clone $baseOrdersQuery)
            ->andWhere([
                '>=', "[[commerce_orders.dateOrdered]]", $this->dates['previousStart']
            ])
            ->andWhere([
                '<', "[[commerce_orders.dateOrdered]]", $this->dates['originalStart']
            ]);

        $adjustmentsQuery = (new Query())
            ->from('{{%commerce_orderadjustments}} commerce_orderadjustments')
            ->select([
                "
                CONCAT('[', GROUP_CONCAT(
                    JSON_OBJECT(
                        'included', [[commerce_orderadjustments.included]],
                        'amount', [[commerce_orderadjustments.amount]]
                    )
                ), ']')
                "
            ])
            ->where("[[commerce_orderadjustments.orderId]] = [[commerce_orders.id]]");

        $purchasableQuery = (new Query())
            ->from('{{%commerce_purchasables}} commerce_purchasables')
            ->select(["JSON_OBJECT(
                'id', [[commerce_purchasables.id]],
                'sku', [[commerce_purchasables.sku]]
            )"])
            ->where("[[commerce_purchasables.id]] = [[commerce_lineitems.purchasableId]]");
            
        $lineItemsQuery = (new Query())
            ->from('{{%commerce_lineitems}} commerce_lineitems')
            ->select([
                "
                CONCAT('[', GROUP_CONCAT(
                    JSON_OBJECT(
                        'orderId', [[commerce_lineitems.orderId]],
                        'purchasableId', [[commerce_lineitems.purchasableId]],
                        'price', [[commerce_lineitems.price]],
                        'salePrice', [[commerce_lineitems.salePrice]],
                        'qty', [[commerce_lineitems.qty]],
                        'purchasable', ($purchasableQuery->rawSql),
                        'snapshot', [[commerce_lineitems.snapshot]]
                    )
                ), ']')
                "
            ])
            ->where("[[commerce_lineitems.orderId]] = [[commerce_orders.id]]");

        $orderStatusesQuery = (new Query())
            ->from('{{%commerce_orderstatuses}} commerce_orderstatuses')
            ->select([
                "
                JSON_OBJECT(
                    'name', [[commerce_orderstatuses.name]],
                    'color', [[commerce_orderstatuses.color]]
                )
                "
            ])
            ->where("[[commerce_orderstatuses.id]] = [[commerce_orders.orderStatusId]]");

        $shippingAddressQuery = (new Query())
            ->from('{{%addresses}} addresses')
            ->select([
                "
                JSON_OBJECT(
                    'countryCode', [[addresses.countryCode]],
                    'administrativeArea', [[addresses.administrativeArea]],
                    'locality', [[addresses.locality]],
                    'dependentLocality', [[addresses.dependentLocality]],
                    'postalCode', [[addresses.postalCode]],
                    'sortingCode', [[addresses.sortingCode]],
                    'addressLine1', [[addresses.addressLine1]],
                    'addressLine2', [[addresses.addressLine2]],
                    'latitude', [[addresses.latitude]],
                    'longitude', [[addresses.longitude]],
                    'firstName', [[addresses.firstName]],
                    'lastName', [[addresses.lastName]],
                    'fullName', [[addresses.fullName]]
                )
                "
            ])
            ->where("[[addresses.id]] = [[commerce_orders.shippingAddressId]]");

        $billingAddressQuery = (new Query())
            ->from('{{%addresses}} addresses')
            ->select([
                "
                JSON_OBJECT(
                    'countryCode', [[addresses.countryCode]],
                    'administrativeArea', [[addresses.administrativeArea]],
                    'locality', [[addresses.locality]],
                    'dependentLocality', [[addresses.dependentLocality]],
                    'postalCode', [[addresses.postalCode]],
                    'sortingCode', [[addresses.sortingCode]],
                    'addressLine1', [[addresses.addressLine1]],
                    'addressLine2', [[addresses.addressLine2]],
                    'latitude', [[addresses.latitude]],
                    'longitude', [[addresses.longitude]],
                    'firstName', [[addresses.firstName]],
                    'lastName', [[addresses.lastName]],
                    'fullName', [[addresses.fullName]]
                )
                "
            ])
            ->where("[[addresses.id]] = [[commerce_orders.billingAddressId]]");

        $activeCustomerOrdersQuery = (new Query())
            ->from('{{%commerce_orders}} commerce_orders')
            ->distinct()
            ->select("COUNT(*)")
            ->where("[[commerce_orders.customerId]] = [[users.id]]")
            ->andWhere([
                '>=', "[[commerce_orders.dateOrdered]]", $sixtyDays
            ]);

        $scopedCustomerOrdersQuery = (new Query())
            ->from('{{%commerce_orders}} commerce_orders')
            ->distinct()
            ->select("COUNT(*)")
            ->where("[[commerce_orders.customerId]] = [[users.id]]")
            ->andWhere([
                '<', "[[commerce_orders.dateOrdered]]", $this->dates['originalStart']
            ]);

            // `commerce_orders`.`dateOrdered` < '2023-06-25 05:00:00'

        $customerQuery = (new Query())
            ->from('{{%users}} users')
            ->select([
                "
                JSON_OBJECT(
                    'email', [[users.email]],
                    'id', [[users.id]],
                    'orderCount', ($scopedCustomerOrdersQuery->rawSql),
                    'activeOrderCount', ($activeCustomerOrdersQuery->rawSql)
                )
                "
            ])
            ->where("[[users.id]] = [[commerce_orders.customerId]]");

        $query = (new Query())
            ->from(['commerce_subquery' => $currentOrdersQuery])
            ->distinct()
            ->select([
                'id' => 'commerce_orders.id',
                'dateOrdered' => 'commerce_orders.dateOrdered',
                'datePaid' => 'commerce_orders.datePaid',
                'orderStatusId' => 'commerce_orders.orderStatusId',
                'customerId' => 'commerce_orders.customerId',
                'email' => 'commerce_orders.email',
                'number' => 'commerce_orders.number',
                'reference' => 'commerce_orders.reference',
                'currency' => 'commerce_orders.currency',
                'paidStatus' => 'commerce_orders.paidStatus',
                'total' => 'commerce_orders.total',
                'totalTax' => 'commerce_orders.totalTax',
                'totalShippingCost' => 'commerce_orders.totalShippingCost',
                'totalDiscount' => 'commerce_orders.totalDiscount',
                'totalPaid' => 'commerce_orders.totalPaid',
                'lineItems' => $lineItemsQuery,
                'adjustments' => $adjustmentsQuery,
                'orderStatus' => $orderStatusesQuery,
                'shippingAddress' => $shippingAddressQuery,
                'billingAddress' => $billingAddressQuery,
                'customer' => $customerQuery
            ])
            ->innerJoin(
                '{{%elements}} elements', '[[elements.id]] = [[commerce_subquery.elementsId]]'
            )
            ->innerJoin(
                '{{%commerce_orders}} commerce_orders', '[[commerce_orders.id]] = [[commerce_subquery.elementsId]]'
            )
            ->innerJoin(
                '{{%content}} content', '[[content.id]] = [[commerce_subquery.contentId]]'
            )
            ->orderBy('[[commerce_orders.dateOrdered]] DESC');

        $result = OrderModel::normalizeArrayedOrders($query->all());

        if ($withPrevious) {

            $query
                ->from(['commerce_subquery' => $previousOrdersQuery]);
 
            $result = [
                'previousPeriod' => OrderModel::normalizeArrayedOrders($query->all()),
                'currentPeriod' => $result
            ];

        }

        return $result;
        
    }

    /**
     * Returns the orders as formatted by the model.
     *
     * @return array
     */
    public function getOrders(): array
    {
        $orders = $this->fetchOrders();
        $statsData = [
            'type' => 'orders',
            'data' => $orders,
            'start' => $this->dates['originalStart'],
            'end' => $this->dates['originalEnd'],
        ];
        $result = [
            'orders' => OrderModel::fromArrayedOrders($orders),
            'stats' => CommerceReports::$plugin->stats->getStats($statsData),
        ];

        return $result;
    }
}
