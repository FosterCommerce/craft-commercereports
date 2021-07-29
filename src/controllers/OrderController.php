<?php

namespace fostercommerce\commerceinsights\controllers;

use Craft;
use craft\commerce\web\twig\Extension;
use craft\web\Controller;

class OrderController extends VueController
{
    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
    }

    public function actionOrderIndex()
    {
        return $this->renderTemplate('commerceinsights/vue/index', [
            'navItem' => 'orders'
        ]);
    }

    public function actionProduct($id) {
        $render = Craft::$app->request->getBodyParam('render') ?? true;

        if($render !== 'false') {
            return $this->renderTemplate('commerceinsights/vue/index', [
                'id' => $id,
                'navItem' => 'orders'
            ]);
        } else {
            return $this->asJson($this->getOrders($id));
        }
    }

    private function getOrders($id)
    {
        $grouped     = [];
        $data        = [];
        $id          = Craft::$app->request->getBodyParam('id') ?? $id;
        $rangeStart  = Craft::$app->request->getBodyParam('range_start') ?? null;
        $rangeEnd    = Craft::$app->request->getBodyParam('range_end') ?? null;
        $variant     = Craft::$app->request->getQueryParam('variant') ?? null;
        $variantType = Craft::$app->request->getQueryParam('variant_type') ?? null;
        $startDate   = Craft::$app->request->getQueryParam('startDate') ?? null;
        $endDate     = Craft::$app->request->getQueryParam('endDate') ?? null;

        if ($startDate && !$rangeStart) {
            $startObj = new \DateTime(urldecode($startDate));
        } elseif ($rangeStart) {
            $startObj = new \DateTime(urldecode($rangeStart));
        } else {
            $startObj = new \DateTime(date('Y-m-d 00:00:00', strtotime('-1 month')));
        }

        if ($endDate && !$rangeEnd) {
            $endObj = new \DateTime(urldecode($endDate));
        } elseif ($rangeEnd) {
            $endObj = new \DateTime(urldecode($rangeEnd));
        } else {
            $endObj = new \DateTime();
        }

        $start = $startObj->format('Y-m-d 00:00:00');
        $end   = $endObj->format('Y-m-d 23:59:59');

        if ($variant || $variantType) {
            $query   = new \yii\db\Query();
            $results = $query->select('orders.id as ID, orders.dateOrdered as dateOrdered, orders.reference, orders.email as email, statuses.name as status, statuses.color as color, lineitems.options, orders.currency as currency, orders.totalPrice as total, orders.totalPaid as amountPaid, shippingAddress.firstName as shippingFirstName, shippingAddress.lastName as shippingLastName, billingAddress.firstName as billingFirstName, billingAddress.lastName as billingLastName')
                             ->from('`craft_commerce_lineitems` `lineitems`')
                             ->join('JOIN', 'craft_commerce_orders AS orders', 'lineitems.orderId = orders.id')
                             ->join('JOIN', 'craft_commerce_purchasables AS purchasables', 'lineitems.purchasableId = purchasables.id')
                             ->join('JOIN', 'craft_commerce_orderstatuses AS statuses', 'orders.orderStatusId = statuses.id')
                             ->join('JOIN', 'craft_commerce_addresses AS shippingAddress', 'orders.shippingAddressId = shippingAddress.id')
                             ->join('JOIN', 'craft_commerce_addresses AS billingAddress', 'orders.billingAddressId = billingAddress.id')
                             ->where("purchasables.id = {$id}")
                             ->andWhere('orders.isCompleted = 1')
                             ->andWhere('orders.orderStatusId < 4')
                             ->andWhere("orders.dateOrdered >= '{$start}'")
                             ->andWhere("orders.dateOrdered <= '{$end}'")
                             ->orderBy('orders.dateOrdered DESC')
                             ->all();

            foreach (array_reverse($results, true) as $index => &$row) {
                $options        = json_decode($row['options']);
                $currency       = $row['currency'];
                $query          = new \yii\db\Query();
                $adjustments    = ['tax' => 0, 'shipping' => 0, 'discount' => 0, 'adjustmentsTotal' => 0];
                $adjustmentsQ   = $query->select('adjustments.type, adjustments.amount')
                                        ->from('`craft_commerce_orderadjustments` `adjustments`')
                                        ->where("adjustments.orderId = {$row['ID']}")
                                        ->all();

                foreach ($adjustmentsQ as $adjustment) {
                    $type = strtolower($adjustment['type']);
                    $adjustments[$type] += $adjustment['amount'];
                    $adjustments['adjustmentsTotal'] += $adjustment['amount'];
                }
            }

            foreach ($grouped as $item => $value) {
                $date = new \DateTime($value['dateOrdered']);

                $data[] = [
                    'orderId'      => $value['id'],
                    'Item'         => $item,
                    'reference'    => $value['reference'],
                    'date'         => $date->format('m/d/Y g:ia'),
                    'fullDate'     => $date->format('l, F j, Y, g:ia'),
                    'dateStamp'    => $date->getTimestamp(),
                    'status'       => $value['status'],
                    'color'        => $value['color'],
                    'base'         => $value['base'],
                    'merchTotal'   => $value['merchTotal'],
                    'tax'          => $value['tax'],
                    'shipping'     => $value['shipping'],
                    'discount'     => $value['discount'],
                    'amountPaid'   => $value['amountPaid'],
                    'billingName'  => $value['billingName'],
                    'shippingName' => $value['shippingName'],
                    'email'        => $value['email'],
                ];
            }
        } else {
            $data = $this->_getOrders($id);
        }

        return $data;
    }
}
