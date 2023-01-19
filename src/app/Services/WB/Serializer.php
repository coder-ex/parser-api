<?php

namespace App\Services\WB;

use Exception;

class Serializer
{
    public function __construct(private string $task, private bool $debug)
    {
        date_default_timezone_set('Europe/Moscow');
    }

    public function __destruct()
    {
        date_default_timezone_set('UTC');
    }

    /**
     * сериализатор данных для БД
     *
     * @param array &$data исходный массив с данными
     * @param integer $projectId id проекта
     * @return array
     */
    public function serialize(array $data, string|int|null $projectId): array|null
    {
        if ($this->task === 'incomes') {
            return $this->serialiserIncomes($data, $projectId);
        } elseif ($this->task === 'stocks') {
            return $this->serializeStocks($data, $projectId);
        } elseif ($this->task === 'orders') {
            return $this->serializeOrders($data, $projectId);
        } elseif ($this->task === 'sales') {
            return $this->serializeSales($data, $projectId);
        } elseif ($this->task === 'reportDetailByPeriod') {
            return $this->serializeSaleReports($data, $projectId);
        } elseif ($this->task === 'excise-goods') {
            return (empty($data)) ? [] : $this-> serialiserExciseGoods($data, $projectId);
        }
    }

    private function serialiserExciseGoods(array $data, string|int $projectId)
    {
        if (count($data) == 0) return $data;

        $res = [];
        try {
            $res['project_id'] = $projectId;

            $res['inn'] = $data['inn'] ?? null;
            $res['finishedPrice'] = $data['finishedPrice'] ?? null;
            $res['operationTypeId'] = $data['operationTypeId'] ?? null;
            $res['fiscalDt'] = $data['fiscalDt'];
            $res['docNumber'] = $data['docNumber'] ?? null;
            $res['fnNumber'] = $data['fnNumber'] ?? null;
            $res['regNumber'] = $data['regNumber'] ?? null;
            $res['excise'] = $data['excise'] ?? null;
            $res['date'] = $data['date'];
        } catch (Exception $e) {
            outMsg($this->task, 'error.', $e->getMessage(), $this->debug);
        }
        //---
        return $res;
    }

    private function serializeSaleReports(array $data, string|int $projectId)
    {
        if (count($data) == 0) return $data;

        $res = [];
        try {
            $res['project_id'] = $projectId;
            $res['realizationreport_id'] = $data['realizationreport_id'] ?? null;
            $res['suppliercontract_code'] = $data['suppliercontract_code'] ?? null;
            $res['rrd_id'] = $data['rrd_id'] ?? null;
            $res['gi_id'] = $data['gi_id'] ?? null;
            $res['subject_name'] = $data['subject_name'] ?? null;
            $res['nm_id'] = $data['nm_id'] ?? null;
            $res['brand_name'] = $data['brand_name'] ?? null;
            $res['sa_name'] = $data['sa_name'] ?? null;
            $res['ts_name'] = $data['ts_name'] ?? null;
            $res['barcode'] = $data['barcode'] ?? null;
            $res['doc_type_name'] = $data['doc_type_name'] ?? null;
            $res['quantity'] = $data['quantity'] ?? null;
            $res['retail_price'] = $data['retail_price'] ?? null;
            $res['retail_amount'] = $data['retail_amount'] ?? null;
            $res['sale_percent'] = $data['sale_percent'] ?? null;
            $res['commission_percent'] = $data['commission_percent'] ?? null;
            $res['office_name'] = $data['office_name'] ?? null;
            $res['supplier_oper_name'] = $data['supplier_oper_name'] ?? null;
            $res['order_dt'] = date("Y-m-d H:i:s", strtotime($data['order_dt'])) ?? null;
            $res['sale_dt'] = date("Y-m-d H:i:s", strtotime($data['sale_dt'])) ?? null;
            $res['rr_dt'] = date("Y-m-d H:i:s", strtotime($data['rr_dt'])) ?? null;
            $res['shk_id'] = $data['shk_id'] ?? null;
            $res['retail_price_withdisc_rub'] = $data['retail_price_withdisc_rub'] ?? null;
            $res['delivery_amount'] = $data['delivery_amount'] ?? null;
            $res['return_amount'] = $data['return_amount'] ?? null;
            $res['delivery_rub'] = $data['delivery_rub'] ?? null;
            $res['gi_box_type_name'] = $data['gi_box_type_name'] ?? null;
            $res['product_discount_for_report'] = $data['product_discount_for_report'] ?? null;
            $res['supplier_promo'] = $data['supplier_promo'] ?? null;
            $res['rid'] = $data['rid'] ?? null;
            $res['ppvz_spp_prc'] = $data['ppvz_spp_prc'] ?? null;
            $res['ppvz_kvw_prc_base'] = $data['ppvz_kvw_prc_base'] ?? null;
            $res['ppvz_kvw_prc'] = $data['ppvz_kvw_prc'] ?? null;
            $res['ppvz_sales_commission'] = $data['ppvz_sales_commission'] ?? null;
            $res['ppvz_for_pay'] = $data['ppvz_for_pay'] ?? null;
            $res['ppvz_reward'] = $data['ppvz_reward'] ?? null;
            $res['ppvz_vw'] = $data['ppvz_vw'] ?? null;
            $res['ppvz_vw_nds'] = $data['ppvz_vw_nds'] ?? null;
            $res['ppvz_office_id'] = $data['ppvz_office_id'] ?? null;
            $res['ppvz_office_name'] = $data['ppvz_office_name'] ?? null;
            $res['ppvz_supplier_id'] = $data['ppvz_supplier_id'] ?? null;
            $res['ppvz_supplier_name'] = $data['ppvz_supplier_name'] ?? null;
            $res['ppvz_inn'] = $data['ppvz_inn'] ?? null;
            $res['declaration_number'] = $data['declaration_number'] ?? null;
            $res['bonus_type_name'] = $data['bonus_type_name'] ?? null;
            $res['sticker_id'] = $data['sticker_id'] ?? null;
            $res['site_country'] = $data['site_country'] ?? null;
            $res['penalty'] = $data['penalty'] ?? null;
            $res['additional_payment'] = $data['additional_payment'] ?? null;
        } catch (Exception $e) {
            outMsg($this->task, 'error.', $e->getMessage(), $this->debug);
        }
        //---
        return $res;
    }

    private function serializeSales(array $data, string|int $projectId)
    {
        if (count($data) == 0) return $data;

        $res = [];
        try {
            $res['project_id'] = $projectId;
            $res['date'] = $data['date'];
            $res['lastChangeDate'] = $data['lastChangeDate'];
            $res['supplierArticle'] = $data['supplierArticle'] ?? null;
            $res['techSize'] = $data['techSize'] ?? null;
            $res['barcode'] = $data['barcode'] ?? null;
            $res['totalPrice'] = $data['totalPrice'] ?? null;
            $res['discountPercent'] = $data['discountPercent'] ?? null;
            $res['isSupply'] = $data['isSupply'] ?? null;
            $res['isRealization'] = $data['isRealization'] ?? null;
            $res['promoCodeDiscount'] = $data['promoCodeDiscount'] ?? null;
            $res['warehouseName'] = $data['warehouseName'] ?? null;
            $res['countryName'] = $data['countryName'] ?? null;
            $res['oblastOkrugName'] = $data['oblastOkrugName'] ?? null;
            $res['regionName'] = $data['regionName'] ?? null;
            $res['incomeID'] = $data['incomeID'] ?? null;
            $res['saleID'] = $data['saleID'] ?? null;
            //$res['status'] = $data['status'] ?? null;
            $res['odid'] = $data['odid'] ?? null;
            $res['spp'] = $data['spp'] ?? null;
            $res['forPay'] = $data['forPay'] ?? null;
            $res['finishedPrice'] = $data['finishedPrice'] ?? null;
            $res['priceWithDisc'] = $data['priceWithDisc'] ?? null;
            $res['nmId'] = $data['nmId'] ?? null;
            $res['subject'] = $data['subject'] ?? null;
            $res['category'] = $data['category'] ?? null;
            $res['brand'] = $data['brand'] ?? null;
            $res['IsStorno'] = $data['IsStorno'] ?? null;
            $res['gNumber'] = $data['gNumber'] ?? null;
            $res['sticker'] = $data['sticker'] ?? null;

        } catch (Exception $e) {
            outMsg($this->task, 'error.', $e->getMessage(), $this->debug);
        }
        //---
        return $res;
    }

    private function serializeOrders(array $data, string|int $projectId)
    {
        if (count($data) == 0) return $data;

        $res = [];
        try {
            $res['project_id'] = $projectId;
            $res['date'] = $data['date'] ?? null;
            $res['lastChangeDate'] = $data['lastChangeDate'];
            $res['supplierArticle'] = $data['supplierArticle'] ?? null;
            $res['techSize'] = $data['techSize'] ?? null;
            $res['barcode'] = $data['barcode'] ?? null;
            $res['totalPrice'] = $data['totalPrice'] ?? null;
            $res['discountPercent'] = $data['discountPercent'] ?? null;
            $res['warehouseName'] = $data['warehouseName'] ?? null;
            $res['oblast'] = $data['oblast'] ?? null;
            $res['incomeID'] = $data['incomeID'] ?? null;
            $res['odid'] = $data['odid'] ?? null;
            $res['nmId'] = $data['nmId'] ?? null;
            $res['subject'] = $data['subject'] ?? null;
            $res['category'] = $data['category'] ?? null;
            $res['brand'] = $data['brand'] ?? null;
            $res['isCancel'] = $data['isCancel'] ?? null;
            $res['cancel_dt'] = $data['cancel_dt'] ?? null;
            $res['gNumber'] = $data['gNumber'] ?? null;
            $res['sticker'] = $data['sticker'] ?? null;
        } catch (Exception $e) {
            outMsg($this->task, 'error.', $e->getMessage(), $this->debug);
        }
        //---
        return $res;
    }

    private function serializeStocks(array $data, string|int $projectId)
    {
        if (count($data) == 0) return $data;

        $res = [];
        try {
            $res['project_id'] = $projectId;
            $res['published_at'] = date('Y-m-d');
            $res['lastChangeDate'] = $data['lastChangeDate'];
            $res['supplierArticle'] = $data['supplierArticle'] ?? null;
            $res['techSize'] = $data['techSize'] ?? null;
            $res['barcode'] = $data['barcode'] ?? null;
            $res['quantity'] = $data['quantity'] ?? null;
            $res['isSupply'] = $data['isSupply'] ?? null;
            $res['isRealization'] = $data['isRealization'] ?? null;
            $res['quantityFull'] = $data['quantityFull'] ?? null;
            $res['quantityNotInOrders'] = $data['quantityNotInOrders'] ?? 0;
            $res['warehouse'] = $data['warehouse'] ?? null;
            $res['warehouseName'] = $data['warehouseName'];
            $res['inWayToClient'] = $data['inWayToClient'] ?? 0;
            $res['inWayFromClient'] = $data['inWayFromClient'] ?? 0;
            $res['nmId'] = $data['nmId'];
            $res['subject'] = $data['subject'] ?? null;
            $res['category'] = $data['category'] ?? null;
            $res['daysOnSite'] = $data['daysOnSite'] ?? null;
            $res['brand'] = $data['brand'] ?? null;
            $res['SCCode'] = $data['SCCode'] ?? null;
            $res['Price'] = $data['Price'] ?? null;
            $res['Discount'] = $data['Discount'] ?? null;
        } catch (Exception $e) {
            outMsg($this->task, 'error.', $e->getMessage(), $this->debug);
        }
        //---
        return $res;
    }

    private function serialiserIncomes(array $data, string|int $projectId)
    {
        if (count($data) == 0) return $data;

        $res = [];
        try {
            $res['project_id'] = $projectId;
            $res['incomeId'] = $data['incomeId'] ?? null;
            $res['number'] = $data['number'] ?? null;
            $res['date'] = $data['date'];
            $res['lastChangeDate'] = $data['lastChangeDate'];
            $res['supplierArticle'] = $data['supplierArticle'] ?? null;
            $res['techSize'] = $data['techSize'] ?? null;
            $res['barcode'] = $data['barcode'] ?? null;
            $res['quantity'] = $data['quantity'] ?? null;
            $res['totalPrice'] = $data['totalPrice'] ?? null;
            $res['dateClose'] = $data['dateClose'];
            $res['warehouseName'] = $data['warehouseName'] ?? null;
            $res['nmId'] = $data['nmId'] ?? null;
            $res['status'] = $data['status'] ?? null;
        } catch (Exception $e) {
            outMsg($this->task, 'error.', $e->getMessage(), $this->debug);
        }
        //---
        return $res;
    }
}
