<?php

namespace App\Services\Roistat;

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
     * @return array
     */
    public function serialize(stdClass|array $data, string|int|null $projectId): array|null
    {
        if ($this->task === 'data') {
            return $this->serialiserData($data, $projectId);
        } elseif ($this->task === 'list-integration') {
            return $this->serializeListIntegration($data, $projectId);
        }
        // elseif ($this->task === 'visit-list') {
        //     return (empty($data)) ? [] : $this->serialiserVisit($data, $projectId);
        // }
    }

    /**
     * сериализатор по методу /project/site/visit/list
     *
     * @param stdClass &$data исходный массив с данными
     * @param integer $projectId id проекта
     * @return array
     */
    private function serialiserVisit(stdClass $data, string|int $projectId): array
    {
        $res = [];
        try {
            $res['project_id'] = $projectId;
            $res['visit_id'] = $data->id;
            $res['first_visit_id'] = $data->first_visit_id;
            //--- для dateTime (нужно преобразование для применения к дате timezone Europe/Moscow)
            $res['date'] = date("Y-m-d H:i:s", strtotime($data->date));
            $res['host'] = isset($data->host) ? $data->host : null;
            $res['cost'] = isset($data->cost) ? $data->cost : null;
            $res['order_ids'] = (count($data->order_ids) == 0) ? null : implode(',', $data->order_ids);
            //--- для дальнейшего переноса в list-integrations (добавлено 2022-09-02)
            $res['source_system_name'] = isset($data->source->system_name) ? $data->source->system_name : null;
            $res['source_display_name'] = isset($data->source->display_name) ? $data->source->display_name : null;
        } catch (Exception $e) {
            throw $e;
        }
        //---
        return $res;
    }

    /**
     * сериализатор по методу list
     *
     * @param stdClass &$data исходный массив с данными
     * @param integer $projectId id проекта
     * @return array
     */
    private function serializeList(stdClass $data, string|int $projectId): array
    {
        $res = [];
        try {
            $res['project_id'] = $projectId;
            $res['source'] = $data->source;
            $res['name'] = isset($data->name) ? $data->name : null;
            $res['type'] = $data->type;
            $res['level'] = $data->level;
            $res['icon'] = $data->ico;
        } catch (Exception $e) {
            throw $e;
        }
        //---
        return $res;
    }

    /**
     * сериализатор по методу data
     *
     * @param stdClass $data исходный массив с данными
     * @param integer $projectId id проекта
     * @return array
     */
    private function serialiserData(stdClass $data, string|int $projectId): array
    {
        $res = [];
        try {
            $res['project_id'] = $projectId;
            $res['marketing_cost'] = $data->metrics[0]->value;
            //$res['fields_work_ABC'] = $data->metrics[1]->value;
            //--- для dateTime (нужно преобразование для применения к дате timezone Europe/Moscow)
            $res['dateFrom'] = date("Y-m-d H:i:s", strtotime($data->dateFrom));
            $res['dateTo'] = date("Y-m-d H:i:s", strtotime($data->dateTo));
            //--- перед записью в таблицу удалить !!!
            $res['marker_level_1_title'] = $data->dimensions->marker_level_1->title ?? null;
            $res['marker_level_1_value'] = $data->dimensions->marker_level_1->value ?? null;
            $res['marker_level_2_title'] = $data->dimensions->marker_level_2->title ?? null;
            $res['marker_level_2_value'] = $data->dimensions->marker_level_2->value ?? null;
            $res['marker_level_3_title'] = $data->dimensions->marker_level_3->title ?? null;
            $res['marker_level_3_value'] = $data->dimensions->marker_level_3->value ?? null;
        } catch (Exception $e) {
            throw $e;
        }
        //---
        return $res;
    }

    /**
     * сериализатор по методу /project/integration/order/list 
     * ВНИМАНИЕ !! перепиливать под objects нельзя т.к. в запросах есть поля с пробелами
     *
     * @param array &$data исходный массив с данными
     * @param integer $projectId id проекта
     * @return array
     */
    private function serializeListIntegration(array $data, string|int $projectId): array
    {
        if (count($data) == 0) return $data;

        $res = [];
        try {
            $res['project_id'] = $projectId;
            $res['order_id'] = $data['id'];
            //--- для status
            $res['status_name'] = $data['status']['name'];
            //--- для visit
            $res['display_name'] = isset($data['visit']['source']['display_name']) ? $data['visit']['source']['display_name'] : null;
            $res['system_name'] = isset($data['visit']['source']['system_name']) ? $data['visit']['source']['system_name'] : null;
            //--- для dateTime (нужно преобразование для применения к дате timezone Europe/Moscow), так же можно попробовать "Y-m-d H:i:sO"
            $res['creation_date'] = isset($data['creation_date']) ? date("Y-m-d H:i:s", strtotime($data['creation_date'])) : null;
            $res['revenue'] = $data['revenue'];
            $res['client_id'] = $data['client_id'] ?? null;
            $res['roistat'] = $data['roistat'];
            //--- для custom_fields
            $res['fields_manager'] = isset($data['custom_fields']['Менеджер']) ? $data['custom_fields']['Менеджер'] : null;
            $res['fields_in_prior'] = isset($data['custom_fields']['Входящий приоритет']) ? $data['custom_fields']['Входящий приоритет'] : null;
            $res['fields_work_prior'] = isset($data['custom_fields']['Рабочий приоритет']) ? $data['custom_fields']['Рабочий приоритет'] : null;
            $res['fields_target_lead'] = isset($data['custom_fields']['Целевой лид']) ? $data['custom_fields']['Целевой лид'] : null;
        } catch (Exception $e) {
            throw $e;
        }
        //---
        return $res;
    }
}
