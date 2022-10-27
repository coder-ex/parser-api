<?php

namespace App\Services\Ozon;

use App\Repositories\Export\ServiceRepository;
use App\Repositories\Ozon\OzonRepository;
use App\Services\Base\BaseService;
use App\Services\Base\InterfaceService;
use App\Services\Exceptions\Http500Exception;
use App\Services\Export\JournalService;
use App\Services\Ozon\Traits\FetchDataFromAPI;
use Illuminate\Support\Str;
use ErrorException;
use Exception;
use Illuminate\Support\Facades\DB;

class RefWarehouseService extends BaseService implements InterfaceService
{
    use FetchDataFromAPI;

    /**
     * конструктор
     *
     * @param [type] $repository
     */
    public function __construct(
        private OzonRepository $repository = new OzonRepository(),
    ) {
        parent::__construct();
        date_default_timezone_set('UTC');   // Ozon работает по UTC
    }

    public function __destruct()
    {
        unset($this->repository);
    }

    /**
     * создание справочника складов на основе ReportStockService
     *
     * @param string $table таблица выгрузки
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param string $urlAPI шаблон url от API Ozon
     * @param string $project идентификатор проекта
     * @param string $secret key проекта Ozon
     * @param string $task задача
     * @return void
     */
    public function run(string $table, string $typeDB, string $urlAPI, string $project, string $secret, string $task)
    {
        //--- проблемы с памятью
        $time_limit = ini_get('max_execution_time');
        $memory_limit = ini_get('memory_limit');
        set_time_limit(0);
        ini_set('memory_limit', -1);

        $journal = new JournalService($typeDB, $project, $task.'-ref-warehouses');
        $journal->startTask();

        try {
            //--- вырезаем json из массива и собираем данные в массив по json для записи
            $serviceRepository = new ServiceRepository();
            $name_project = $serviceRepository->getProjects($typeDB, $project)?->first()?->name;

            //--- получим данные из таблицы
            $catalogDB = DB::connection($typeDB)->table("ozon_{$name_project}_catalog_report_stocks")->get()->toArray();

            //--- подготовим справочник складов
            foreach ($catalogDB as $key => $value) {
                unset($catalogDB[$key]->id);
                unset($catalogDB[$key]->value);
                unset($catalogDB[$key]->{"fk_ozon_{$name_project}_report_stocks_id"});
            }

            $catalogDB = array_map('unserialize', array_unique(array_map('serialize', $catalogDB)));

            foreach ($catalogDB as $key => $value) {
                $name = explode(' на складе ', $value->{'name'})[1];
                $name = explode(',', $name)[0];
                $catalogDB[$key]->{'name'} = $name;
            }

            $catalogDB = array_map('unserialize', array_unique(array_map('serialize', $catalogDB)));

            $dataDB = [];
            foreach ($catalogDB as $key => $value) {
                $dataDB[] = [
                    'id' => Str::uuid(),
                    'name' => $catalogDB[$key]->name
                ];
            }

            //--- пишем в справочник ref-warehouses
            foreach (array_chunk($dataDB, 1000) as $chunk) {
                $this->repository->upsert("ozon_{$name_project}_ref_warehouses", $typeDB, $chunk);
            }
        } catch (Exception | ErrorException | Http500Exception $e) {
            $journal->upTask('ERROR', $e->getMessage());
            outMsg($task, 'error.', $e->getMessage(), $this->debug);
            return;
        }

        $journal->upTask('OK');     // обновим запись в журнале

        unset($dataDB);
        unset($catalogDB);
    }

    public function createUrl(string $urlAPI, ?string $type = ''): string
    {
        return "";
    }
}
