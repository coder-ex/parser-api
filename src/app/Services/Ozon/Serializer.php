<?php

namespace App\Services\Ozon;

use ErrorException;
use Exception;
use stdClass;

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
     * @param array|stdClass &$data исходный массив с данными
     * @param integer $projectId id проекта
     * @param array|null $name
     * @return array
     */
    public function serialize(stdClass|array $data, string|int|null $projectId, ?array $name=null): array|null
    {
        if ($this->task === 'stock-warehouses') {
            return $this->serializeStockWarehouses($data, $projectId);
        } elseif ($this->task === 'fbo-list') {
            return $this->serializeFboList($data, $projectId);
        } elseif ($this->task === 'campaign') {
            return $this->serializeCampaign($data, $projectId);
        } elseif ($this->task === 'statistics-daily') {
            return $this->serializeStatDaily($data, $projectId);
        } elseif ($this->task === 'statistics-media-compaign') {
            return $this->serializeStatMediaCampaign($data, $projectId);
        } elseif ($this->task === 'statistics-food-compaign') {
            return $this->serializeStatFoodCampaign($data, $projectId);
        } elseif ($this->task === 'statistics-expense-compaign') {
            return $this->serializeStatExpenseCampaign($data, $projectId);
        } elseif ($this->task === 'report-stocks') {
            return $this->serializeReportStocks($data, $projectId, $name);
        }
    }

    private function serializeReportStocks(array $data, string $projectId, array $name): array
    {
        if (count($data) == 0) return $data;

        $res = [];
        try {
            $res['project_id'] = $projectId;
            $res['published_at'] = date('Y-m-d');
            $res['article'] = $data[0] ?? null;
            $res['product_id'] = (int)$data[1] ?? null;
            $res['sku_id'] = (int)$data[2] ?? null;
            $res['product_name'] = $data[3] ?? null;
            $res['barcode'] = (int)$data[4] ?? null;
            $res['product_status'] = $data[5] ?? null;
            $res['site_visibility'] = $data[6] ?? null;
            $res['total_warehouse'] = (int)$data[7] ?? null;
            $res['total_reserv'] = (int)$data[8] ?? null;

            $array = [];
            foreach ($data as $key => $value) {
                if ($key < 9) continue;
                $array[] = ['name' => $name[$key], 'value'=> $value];
            }

            $res['catalog_json'] = json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (Exception | ErrorException $e) {
            throw $e;
        }
        //---
        return $res;
    }

    private function serializeStatExpenseCampaign(array $data, string|int $projectId): array
    {
        if (count($data) == 0) return $data;

        $res = [];
        try {
            $res['project_id'] = $projectId;
            $res['campaign_id'] = $data[0] ?? null;
            $res['date'] = $data[1] ?? null;
            $res['title'] = $data[2] ?? null;
            $res['cost'] = floatval(str_replace(',', '.', $data[3])) ?? 0.00;
            $res['costBonus'] = floatval(str_replace(',', '.', $data[4])) ?? 0.00;
        } catch (Exception | ErrorException $e) {
            throw $e;
        }
        //---
        return $res;
    }

    private function serializeStatFoodCampaign(array $data, string|int $projectId): array
    {
        if (count($data) == 0) return $data;

        $res = [];
        try {
            $res['project_id'] = $projectId;
            $res['published_at'] = $data[0] ?? null;
            $res['campaign_id'] = $data[1] ?? null;
            $res['title'] = $data[2] ?? null;
            $res['status'] = $data[3] ?? null;
            $res['dailyBudget'] = floatval(str_replace(',', '.', $data[4])) ?? 0.00;
            $res['cost'] = floatval(str_replace(',', '.', $data[5])) ?? 0.00;
            $res['showing'] = (int)$data[6] ?? 0;
            $res['clicks'] = (int)$data[7] ?? 0;
            $res['averRate'] = floatval(str_replace(',', '.', $data[8])) ?? 0.00;
            $res['averPrice'] = floatval(str_replace(',', '.', $data[9])) ?? 0.00;
            $res['CTR'] = floatval(str_replace(',', '.', $data[10])) ?? 0.00;
            $res['averCPC'] = $data[11] ?? null;
            $res['orderPiece'] = (int)$data[12] ?? 0;
            $res['orderRub'] = floatval(str_replace(',', '.', $data[13])) ?? 0.00;
            $res['PAE'] = floatval(str_replace(',', '.', $data[14])) ?? 0.00;
        } catch (Exception | ErrorException $e) {
            throw $e;
        }
        //---
        return $res;
    }

    private function serializeStatMediaCampaign(array $data, string|int $projectId): array
    {
        if (count($data) == 0) return $data;

        $res = [];
        try {
            $res['project_id'] = $projectId;
            $res['published_at'] = $data[0] ?? null;
            $res['campaign_id'] = $data[1] ?? null;
            $res['title'] = $data[2] ?? null;
            $res['format'] = $data[3] ?? null;
            $res['status'] = $data[4] ?? null;
            $res['dailyBudget'] = floatval(str_replace(',', '.', $data[5])) ?? 0.00;
            $res['budget'] = $data[6] ?? null;
            $res['prior'] = $data[7] ?? null;
            $res['cost'] = floatval(str_replace(',', '.', $data[8])) ?? 0.00;
            $res['showing'] = (int)$data[9] ?? 0;
            $res['clicks'] = (int)$data[10] ?? 0;
            $res['averRate'] = floatval(str_replace(',', '.', $data[11])) ?? 0.00;
            $res['averPrice'] = floatval(str_replace(',', '.', $data[12])) ?? 0.00;
            $res['CTR'] = floatval(str_replace(',', '.', $data[13])) ?? 0.00;
            $res['orderPiece'] = (int)$data[14] ?? 0;
            $res['orderRub'] = floatval(str_replace(',', '.', $data[15])) ?? 0.00;
            $res['PAE'] = floatval(str_replace(',', '.', $data[16])) ?? 0.00;
        } catch (Exception | ErrorException $e) {
            throw $e;
        }
        //---
        return $res;
    }

    private function serializeStatDaily(array $data, string|int $projectId): array
    {
        if (count($data) == 0) return $data;

        $res = [];
        try {
            $res['project_id'] = $projectId;
            $res['campaign_id'] = $data[0] ?? null;
            $res['name'] = $data[1] ?? null;
            $res['date'] = date('Y-m-d', strtotime($data[2]));
            $res['showing'] = $data[3] ?? null;
            $res['clicks'] = $data[4] ?? null;
            $res['expense_money'] = floatval(str_replace(',', '.', $data[5])) ?? null;
            $res['average_money_rate'] = floatval(str_replace(',', '.', $data[6])) ?? null;
            $res['orders_quantity'] = $data[7] ?? null;
            $res['orders_money'] = floatval(str_replace(',', '.', $data[8])) ?? null;
        } catch (Exception | ErrorException $e) {
            throw $e;
        }
        //---
        return $res;
    }

    /**
     * сериализатор по методу /api/client/campaign
     *
     * @param array $data
     * @param string|integer $projectId
     * @return array
     */
    private function serializeCampaign(array $data, string|int $projectId): array
    {
        if (count($data) == 0) return $data;

        $res = [];
        try {
            $res['project_id'] = $projectId;
            $res['published_at'] = date('Y-m-d');
            $res['campaign_id'] = $data['id'] ?? null;
            $res['title'] = $data['title'] ?? null;
            $res['state'] = $data['state'] ?? null;
            $res['advObjectType'] = $data['advObjectType'] ?? null;
            $res['fromDate'] = $data['fromDate'] ?? null;
            $res['toDate'] = $data['toDate'] ?? null;
            $res['dailyBudget'] = $data['dailyBudget'] ?? null;
            $res['placement'] = isset($data['placement']) ? json_encode($data['placement']) : null;
            $res['budget'] = $data['budget'] ?? null;
            $res['createdAt'] = formatDateTo($data['createdAt'], 'Y-m-d H:i:s.v', 'UTC') ?? null;   // formatDateTo($data['createdAt'], 'Y-m-d\TH:i:s.v\Z', 'UTC') ?? null;
            $res['updatedAt'] = formatDateTo($data['updatedAt'], 'Y-m-d H:i:s.v', 'UTC') ?? null;   // formatDateTo($data['updatedAt'], 'Y-m-d\TH:i:s.v\Z', 'UTC')?? null;
            $res['productCampaignMode'] = $data['productCampaignMode'] ?? null;
            $res['productAutopilotStrategy'] = $data['productAutopilotStrategy'] ?? null;
            $res['autopilot'] = isset($data['autopilot']) ? json_encode($data['autopilot']) : null;
        } catch (Exception $e) {
            throw $e;
        }
        //---
        return $res;
    }

    /**
     * сериализатор по методу /v3/product/info/stocks
     *
     * @param array &$data исходный массив с данными
     * @param integer $projectId id проекта
     * @return array
     */
    private function serializeStockWarehouses(array $data, string|int $projectId): array
    {
        if (count($data) == 0) return $data;

        $res = [];
        try {
            $res['project_id'] = $projectId;
            $res['date'] = formatDateTo(date('Y-m-d H:i:s'), 'Y-m-d H:i:s', 'UTC');   //date('Y-m-d H:i:s');
            $res['offer_id'] = $data['offer_id'] ?? null;
            $res['product_id'] = $data['product_id'] ?? null;

            foreach ($data['stocks'] as $item) {
                if ($item['type'] === 'fbo') {
                    $res['stocks_fbo_present'] = $item['present'] ?? null;
                } elseif ($item['type'] === 'fbs') {
                    $res['stocks_fbs_present'] = $item['present'] ?? null;
                }
            }
        } catch (Exception $e) {
            throw $e;
        }
        //---
        return $res;
    }


    /**
     * сериализатор по методу /v2/posting/fbo/list
     *
     * @param array &$data исходный массив с данными
     * @param integer $projectId id проекта
     * @return array
     */
    private function serializeFboList(array $data, string|int $projectId): array
    {
        if (count($data) == 0) return $data;

        $res = [];
        try {
            $res['project_id'] = $projectId;
            //'date'] = formatDateTo(date("Y-m-d H:i:s"));
            $res['city'] = $data['analytics_data']['city'] ?? null;
            $res['delivery_type'] = $data['analytics_data']['delivery_type'] ?? null;
            $res['is_premium'] = $data['analytics_data']['is_premium'] ?? null;
            $res['payment_type_group_name'] = $data['analytics_data']['payment_type_group_name'] ?? null;
            $res['region'] = $data['analytics_data']['region'] ?? null;
            $res['warehouse_id'] = $data['analytics_data']['warehouse_id'] ?? null;
            $res['warehouse_name'] = $data['analytics_data']['warehouse_name'] ?? null;
            $res['cancel_reason_id'] = $data['cancel_reason_id'] ?? null;
            //--- для dateTime (нужно преобразование для применения к дате timezone Europe/Moscow)
            $res['created_at'] = isset($data['created_at']) ? formatDateTo($data['created_at'], 'Y-m-d H:i:s.v', 'UTC') : null;
            $res['in_process_at'] = isset($data['in_process_at']) ? formatDateTo($data['in_process_at'], 'Y-m-d H:i:s.v', 'UTC') : null;
            $res['moment'] = isset($data['financial_data']['products'][0]['picking']['moment']) ? formatDateTo($data['financial_data']['products'][0]['picking']['moment'], 'Y-m-d H:i:s.v', 'UTC') : null;
            //---
            $res['actions'] = $data['financial_data']['products'][0]['actions'][0] ?? null;
            $res['client_price'] = $data['financial_data']['products'][0]['client_price'] ?? null;
            $res['commission_amount'] = $data['financial_data']['products'][0]['commission_amount'] ?? null;
            $res['commission_percent'] = $data['financial_data']['products'][0]['commission_percent'] ?? null;
            $res['old_price'] = $data['financial_data']['products'][0]['old_price'] ?? null;
            $res['payout'] = $data['financial_data']['products'][0]['payout'] ?? null;
            $res['amount'] = $data['financial_data']['products'][0]['picking']['amount'] ?? null;
            $res['price'] = $data['financial_data']['products'][0]['price'] ?? null;
            $res['product_id'] = $data['financial_data']['products'][0]['product_id'] ?? null;
            $res['quantity'] = $data['financial_data']['products'][0]['quantity'] ?? null;
            $res['total_discount_percent'] = $data['financial_data']['products'][0]['total_discount_percent'] ?? null;
            $res['total_discount_value'] = $data['financial_data']['products'][0]['total_discount_value'] ?? null;
            $res['order_id'] = $data['order_id'] ?? null;
            $res['order_number'] = $data['order_number'] ?? null;
            $res['posting_number'] = $data['posting_number'] ?? null;
            $res['products'] = isset($data['products']) ? json_encode($data['products']) : null;
            $res['status'] = $data['status'] ?? null;
            //--- добавление к ТЗ от Ильи
            $res['marketplace_service_item_fulfillment'] = $data['financial_data']['products'][0]['item_services']['marketplace_service_item_fulfillment'] ?? null;
            $res['marketplace_service_item_pickup'] = $data['financial_data']['products'][0]['item_services']['marketplace_service_item_pickup'] ?? null;
            $res['marketplace_service_item_dropoff_pvz'] = $data['financial_data']['products'][0]['item_services']['marketplace_service_item_dropoff_pvz'] ?? null;
            $res['marketplace_service_item_dropoff_sc'] = $data['financial_data']['products'][0]['item_services']['marketplace_service_item_dropoff_sc'] ?? null;
            $res['marketplace_service_item_dropoff_ff'] = $data['financial_data']['products'][0]['item_services']['marketplace_service_item_dropoff_ff'] ?? null;
            $res['marketplace_service_item_direct_flow_trans'] = $data['financial_data']['products'][0]['item_services']['marketplace_service_item_direct_flow_trans'] ?? null;
            $res['marketplace_service_item_return_flow_trans'] = $data['financial_data']['products'][0]['item_services']['marketplace_service_item_return_flow_trans'] ?? null;
            $res['marketplace_service_item_deliv_to_customer'] = $data['financial_data']['products'][0]['item_services']['marketplace_service_item_deliv_to_customer'] ?? null;
            $res['marketplace_service_item_return_not_deliv_to_customer'] = $data['financial_data']['products'][0]['item_services']['marketplace_service_item_return_not_deliv_to_customer'] ?? null;
            $res['marketplace_service_item_return_part_goods_customer'] = $data['financial_data']['products'][0]['item_services']['marketplace_service_item_return_part_goods_customer'] ?? null;
            $res['marketplace_service_item_return_after_deliv_to_customer'] = $data['financial_data']['products'][0]['item_services']['marketplace_service_item_return_after_deliv_to_customer'] ?? null;
        } catch (Exception $e) {
            throw $e;
        }
        //---
        return $res;
    }
}
