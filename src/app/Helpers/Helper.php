<?php

use App\Helpers\TypeNotify;
use App\Helpers\TypeTask;
use App\Models\Export\ExportService;
use App\Services\Telegram\TelegramNotifierService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

function getArrayTask(string $apiName): array|null
{
    if ($apiName === 'wb') {
        return [
            TypeTask::Incomes->value,
            TypeTask::Stocks->value,
            TypeTask::Orders->value,
            TypeTask::Sales->value,
            TypeTask::SalesReports->value,
            TypeTask::ExciseReports->value,
        ];
    } elseif ($apiName === 'ozon') {
        return [
            TypeTask::StockWarehouses->value,
            TypeTask::FboList->value,
        ];
    } elseif ($apiName === 'ozon-performance') {
        return [
            TypeTask::Campaign->value,
            TypeTask::StatDaily->value,
            TypeTask::StatMediaCampaign->value,
            TypeTask::StatFoodCampaign->value,
            TypeTask::StatExpenseCampaign->value,
        ];
    } elseif ($apiName === 'roistat') {
        return [
            TypeTask::Data->value,
            TypeTask::ListIntegration->value,
            // TypeTask::VisitList->value,
        ];
    }
    //---
    return null;
}

/**
 * получение данных конфигурации
 *
 * @param string $typeDB БД источник (pgsql vs mysql)
 * @param string $task задача
 * @return void
 */
function getConfigData(string $typeDB, string $task)
{
    $stores = ExportService::on($typeDB)->get()->all();
    foreach ($stores as $store) {
        return $store->tasks()->where('task', $task)->first();
    }
}

/**
 * возвращает параметры API (имя api и имя класса)
 *
 * @param string $task
 * @param string $name имя проекта по Export
 * @return array
 */
function getApiParam(string $task, string $name): array|null
{
    return match ($task) {
        // wb
        'incomes' => ['apiName' => 'wb', 'table' => 'wb_' . strtolower($name) . '_incomes'],
        'stocks' => ['apiName' => 'wb', 'table' => 'wb_' . strtolower($name) . '_stocks'],
        'orders' => ['apiName' => 'wb', 'table' => 'wb_' . strtolower($name) . '_orders'],
        'sales' => ['apiName' => 'wb', 'table' => 'wb_' . strtolower($name) . '_sales'],
        'reportDetailByPeriod' => ['apiName' => 'wb', 'table' => 'wb_' . strtolower($name) . '_sale_reports'],
        'excise-goods' => ['apiName' => 'wb', 'table' => 'wb_' . strtolower($name) . '_excise_reports'],
        // ozon
        'stock-warehouses' => ['apiName' => 'ozon', 'table' => 'ozon_' . strtolower($name) . '_stock_warehouses'],
        'fbo-list' => ['apiName' => 'ozon', 'table' => 'ozon_' . strtolower($name) . '_fbo_lists'],
        'campaign' => ['apiName' => 'ozon-performance', 'table' => 'ozon_' . strtolower($name) . '_campaign'],
        'statistics-daily' => ['apiName' => 'ozon-performance', 'table' => 'ozon_' . strtolower($name) . '_statistics_daily'],
        'statistics-media-compaign' => ['apiName' => 'ozon-performance', 'table' => 'ozon_' . strtolower($name) . '_campaign_media'],
        'statistics-food-compaign' => ['apiName' => 'ozon-performance', 'table' => 'ozon_' . strtolower($name) . '_campaign_foods'],
        'statistics-expense-compaign' => ['apiName' => 'ozon-performance', 'table' => 'ozon_' . strtolower($name) . '_campaign_expenses'],
        // roistat
        'data' => ['apiName' => 'roistat', 'table' => 'roistat_' . strtolower($name) . '_data'],
        'list-integration' => ['apiName' => 'roistat', 'table' => 'roistat_' . strtolower($name) . '_list_integrations'],
            //        'visit-list' => ['apiName' => 'roistat', 'table' => 'roistat_' . strtolower($name) . '_visit_lists'],
        default => null,
    };
}

/**
 * запись результата в файл
 *
 * @param string $task задача
 * @param array &$data массив с данными
 * @param string $dateTo по какую дату собраны данные
 * @return void
 */
function dataResult(string $task, array &$data, string $dateTo = '')
{
    $date = ($dateTo === '') ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s', strtotime($dateTo));
    File::put(
        storage_path('app/' . $task . '-' . $date . '-' . strtotime($date) . '.json'),
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

/**
 * метка success по окончании выполнения всех операций, для debug
 *
 * @param string $task
 * @return void
 */
function dataSuccess(string $task)
{
    File::put(
        storage_path('app/' . $task . '-success-' . time() . '.json'),
        json_encode(['success' => 'транзакции завершены'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

/**
 * для тестового восстановлени данных в БД из файла
 *
 * @param string $task
 * @param string $dateFile
 * @return void
 */
function loadResult(string $task, string $dateFile)
{
    try {
        return json_decode(
            File::get(
                storage_path('app/' . $task . '-' . $dateFile . '.json')
            ),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );
    } catch (Exception $e) {
        return $e;
    }
}

/**
 * загрузка файла по имени из storage/app/...
 *
 * @param string $fileName
 * @return array
 */
function loadConfig(string $fileName)
{
    try {
        return json_decode(
            File::get(
                storage_path("app/{$fileName}.json")
            ),
            false,
            512,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );
    } catch (Exception $e) {
        return $e;
    }
}

/**
 * вывод сообщений
 *
 * @param string $task
 * @param string $msg
 * @param bool $debug если true то в файл, иначе сообщением в Telegram
 * @return void
 */
function outMsg(string $task, string $typeMsg, string $msg, bool $debug)
{
    $date = date('Y-m-d H:i:s');

    if ($debug) {
        File::put(
            storage_path("app/{$task}-{$typeMsg}-{$date}.json"),
            json_encode($msg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    } else {
        $telegram = new TelegramNotifierService(env('TELEGRAM_TOKEN'), env('TELEGRAM_ID'));
        $telegram->notify(
            TypeNotify::Text,
            [
                'text' => "task: {$task} - [{$date} : {$msg}]",
            ]
        );
    }
}

function formatDateTo($date, $format = DateTime::RFC3339, string $timezoneId = 'Europe/Moscow')
{
    date_default_timezone_set($timezoneId);                     // для коррекции тайм-зоны
    return date($format, strtotime($date));
}

function validateDate($date, $format = 'Y-m-d'): bool
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}

function validateTime($time, $format = 'H:i')
{
    $d = DateTime::createFromFormat($format, $time);
    return $d && $d->format($format) == $time;
}

/**
 * запишем последнюю дату в кэш
 *
 * @param string $table таблица выгрузки
 * @param string $project
 * @param string $dateTime рабочий параметр в кэш
 * @param string $task идентификатор для контроля кэша при большом количестве записей
 * @param int $expiration по умолчанию срок экспирации 30 суток
 * @return void
 */
function cachePut(string $table, string $project, string $dateTime, string $task, int $expiration = 2592000)
{
    $keyCache = "{$table}-{$project}";
    Cache::store(env('CACHE_DRIVER', 'file'))->put($keyCache, json_encode(['dateTime' => $dateTime, 'task' => $task]), $expiration);
}

/**
 * получить данные из кеш
 *
 * @param string $table таблица выгрузки
 * @param string $project
 * @return mixed
 */
function cacheGet(string $table, string $project): mixed
{
    $keyCache = "{$table}-{$project}";
    $value = Cache::store(env('CACHE_DRIVER', 'file'))->get($keyCache);
    if (is_string($value)) {
        return json_decode($value)->dateTime ?? null;
    }
    //---
    return null;
}
