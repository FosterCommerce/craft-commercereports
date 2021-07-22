<?php

namespace fostercommerce\commerceinsights\controllers;

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

    private function getOrders($id)
    {
        $todayObj    = new \DateTime();
        $grouped     = [];
        $data        = [];
        $variant     = $_GET['variant'] ?? null;
        $variantType = $_GET['variant_type'] ?? null;
        $color       = $_GET['color'] ?? null;
        $id          = isset($_POST['id']) ? $_POST['id'] : $id;

        if (isset($_GET['startDate']) && !isset($_POST['range_start'])) {
            $startObj = new \DateTime(urldecode($_GET['startDate']));
        } elseif (isset($_POST['range_start'])) {
            $startObj = new \DateTime(urldecode($_POST['range_start']));
        } else {
            $startObj = new \DateTime(date('Y-m-d 00:00:00', strtotime('-1 month')));
        }

        if (isset($_GET['endDate']) && !isset($_POST['range_end'])) {
            $endObj = new \DateTime(urldecode($_GET['endDate']));
        } elseif (isset($_POST['range_end'])) {
            $endObj = new \DateTime(urldecode($_POST['range_end']));
        } else {
            $endObj = $todayObj;
        }

        $start = $startObj->format('Y-m-d 00:00:00');
        $end   = $endObj->format('Y-m-d 23:59:59');

        if ($variant || $variantType || $color) {
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
                $hasPlateColor  = isset($options->{'plate-color'});
                $hasLetterColor = isset($options->{'letter-color'});
                $hasLetters     = isset($options->letters);
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

                if ($hasPlateColor) {
                    $plateKey = $options->{'plate-color'};

                    if (trim(strtoupper($color)) === trim(strtoupper($plateKey)) || !$color) {
                        $grouped[$row['ID']] = [
                            'id'           => $row['ID'],
                            'reference'    => $row['reference'],
                            'dateOrdered'  => $row['dateOrdered'],
                            'status'       => $row['status'],
                            'color'        => $row['color'],
                            'base'         => static::convertCurrency(($row['total'] - $adjustments['adjustmentsTotal']), $currency),
                            'merchTotal'   => static::convertCurrency(($row['total'] - $adjustments['tax'] - $adjustments['discount']), $currency),
                            'tax'          => static::convertCurrency($adjustments['tax'], $currency),
                            'shipping'     => static::convertCurrency($adjustments['shipping'], $currency),
                            'discount'     => static::convertCurrency($adjustments['discount'], $currency),
                            'amountPaid'   => static::convertCurrency($row['amountPaid'], $currency),
                            'billingName'  => $row['billingFirstName'] . ' ' . $row['billingLastName'],
                            'shippingName' => $row['shippingFirstName'] . ' ' . $row['shippingLastName'],
                            'email'        => $row['email']
                        ];
                    }
                }

                if ($hasLetterColor) {
                    $letterColor = ucfirst($options->{'letter-color'}) . ' ';
                } else {
                    $letterColor = null;
                }

                if ($hasLetters) {
                    $letters = str_split(str_replace(' ', '', $options->letters));

                    foreach ($letters as $letter) {
                        if ($hasLetters && !$hasPlateColor) {
                            $letter  = strtoupper($letter);
                            $variant = strtoupper($variant);
                        }

                        if (($letter === $variant && $letterColor === $color) || (!$variant && !$color)) {
                            $grouped[$row['ID']] = [
                                'id'          => $row['ID'],
                                'reference'   => $row['reference'],
                                'dateOrdered' => $row['dateOrdered'],
                                'status'      => $row['status'],
                                'color'       => $row['color'],
                                'base'        => static::convertCurrency(($row['total'] - $adjustments['adjustmentsTotal']), $currency),
                                'merchTotal'  => static::convertCurrency(($row['total'] - $adjustments['tax'] - $adjustments['discount'] - $adjustments['shipping']), $currency),
                                'tax'         => static::convertCurrency($adjustments['tax'], $currency),
                                'shipping'    => static::convertCurrency($adjustments['shipping'], $currency),
                                'discount'    => static::convertCurrency($adjustments['discount'], $currency),
                                'amountPaid'  => static::convertCurrency($row['amountPaid'], $currency),
                                'billingName'  => $row['billingFirstName'] . ' ' . $row['billingLastName'],
                                'shippingName' => $row['shippingFirstName'] . ' ' . $row['shippingLastName'],
                                'email'        => $row['email']
                            ];
                        }
                    }
                }

                if (!$hasLetters && !$hasPlateColor) {
                    $itemTitle = $row['ID'];

                    if (!array_key_exists($itemTitle, $grouped)) {
                        $grouped[$row['ID']] = [
                            'id'          => $row['ID'],
                            'reference'   => $row['reference'],
                            'dateOrdered' => $row['dateOrdered'],
                            'status'      => $row['status'],
                            'color'       => $row['color'],
                            'base'        => static::convertCurrency(($row['total'] - $adjustments['adjustmentsTotal']), $currency),
                            'merchTotal'  => static::convertCurrency(($row['total'] - $adjustments['tax'] - $adjustments['discount']), $currency),
                            'tax'         => static::convertCurrency($adjustments['tax'], $currency),
                            'shipping'    => static::convertCurrency($adjustments['shipping'], $currency),
                            'discount'    => static::convertCurrency($adjustments['discount'], $currency),
                            'amountPaid'  => static::convertCurrency($row['amountPaid'], $currency),
                            'billingName'  => $row['billingFirstName'] . ' ' . $row['billingLastName'],
                            'shippingName' => $row['shippingFirstName'] . ' ' . $row['shippingLastName'],
                            'email'        => $row['email']
                        ];
                    }
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
            $this->start_date = $start;
            $this->end_date = $end;
            $data = $this->_getOrders($id);
        }

        return $data;
    }
}
