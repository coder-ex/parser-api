<?php

namespace App\Console\Commands\MariaBA;

use App\Repositories\Base\Repository;
use ErrorException;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use stdClass;

class DistortionFieldConsole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maria:distortion';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Искажение данных в полях таблицы';

    /**
     * Создать новый экземпляр команды
     */
    public function __construct(
        private Repository $repository = new Repository()
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //--- проблемы с памятью
        $time_limit = ini_get('max_execution_time');
        $memory_limit = ini_get('memory_limit');
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $typeDB = 'pgsql_2';

        $tables = [
            'excise_reports',
            'incomes',
            'orders',
            'sales',
            'sales_reports',
            'stocks'
        ];

        foreach ($tables as $item) {
            $entity = "wb_kulemina_{$item}";

            if (!$this->repository->isTable($typeDB, $entity)) {
                continue;
            }

            try {
                $cnt = 0;
                foreach ($this->selectSearchField($item) as $field) {
                    $dataField = $this->containsRepeats($typeDB, $entity, $field);
                    $this->handleFieldTbl($typeDB, $entity, $field, $dataField, $cnt);
                    unset($dataField);

                    $dataField = $this->doesNotContainOccurrence($typeDB, $entity, $field);
                    $this->additionalHandlerTbl($typeDB, $entity, $field, $dataField, $cnt);
                    unset($dataField);

                    $cnt++;
                }
            } catch (Exception $e) {
                outMsg('copy-db', 'error.', $e->getMessage(), true);
                continue;
            }
        }
        //---
        return 0;
    }

    /**
     * дополнительный обработчик таблицы
     *
     * @param string $typeDB
     * @param string $entity
     * @param string $field
     * @param array $data
     * @return void
     */
    private function additionalHandlerTbl(string $typeDB, string $entity, string $field, array $data, int &$cnt)
    {
        $cnt++;
        $dataField = [];

        try {
            foreach ($data as $value) {
                $el = DB::connection($typeDB)->table($entity)->where($field, '=', $value->{$field})->first();
                $el->{$field} = $field . " {$cnt}";
                $dataField[] = $el;

                $cnt++;
            }

            foreach (array_chunk($dataField, 1000) as $chunk) {
                $this->update($typeDB, $entity, $chunk, $field);
            }
        } catch (Exception | ErrorException $e) {
            throw $e;
        }

        unset($data);
        unset($dataField);
    }

    /**
     * обработчик полей таблицы
     *
     * @param string $entity
     * @param array $data
     * @return void
     */
    private function handleFieldTbl(string $typeDB, string $entity, string $field, array $data, int &$cnt)
    {
        $cnt++;

        try {
            foreach ($data as $value) {
                $dataField = DB::connection($typeDB)->table($entity)->where($field, '=', $value->{$field})->get()->toArray();
                foreach ($dataField as $item) {
                    $item->{$field} = $field . " {$cnt}";
                }

                $cnt++;

                foreach (array_chunk($dataField, 1000) as $chunk) {
                    $this->update($typeDB, $entity, $chunk, $field);
                }
            }
        } catch (Exception | ErrorException $e) {
            throw $e;
        }

        unset($dataField);
    }

    /**
     * выбор поля для поиска
     *
     * @param string $task
     * @return array
     */
    private function selectSearchField(string $task): array
    {
        if ($task === 'incomes') {
            return ['supplierArticle'];
        } elseif ($task === 'orders' || $task === 'sales' || $task === 'stocks') {
            return ['supplierArticle', 'category', 'brand', 'subject'];
        } elseif ($task === 'sales_reports') {
            return ['subject_name', 'brand_name', 'sa_name'];
        }
        //---
        return [];
    }

    /**
     * не содержит вхождение
     *
     * @param string $typeDB
     * @param string $entity
     * @param string $field
     * @return array
     */
    private function doesNotContainOccurrence(string $typeDB, string $entity, string $field): array
    {
        //$query = "select \"" . $field . "\" from " . $entity . " where \"" . $field . "\" not in( select \"" . $field . "\" from " . $entity . " where \"" . $field . "\" Like '" . $field . "%' )";
        $query = "select \"" . $field . "\" from " . $entity . " where \"" . $field . "\" not Like '" . $field . "%'";
        return DB::connection($typeDB)->select($query);
    }

    /**
     * поиск повторов
     *
     * @param string $entity
     * @return array
     */
    private function containsRepeats(string $typeDB, string $entity, string $field): array
    {
        $query = "select \"" . $field . "\", count(*) from " . $entity . " group by \"" . $field . "\" HAVING count(*) > 1";
        return DB::connection($typeDB)->select($query);
    }

    private function update(string $typeDB, string $entity, array|object $data, string $field)
    {
        DB::connection($typeDB)->beginTransaction();
        try {
            foreach ($data as $value) {
                DB::connection($typeDB)->table($entity)->upsert(($value instanceof stdClass) ? (array)$value : $value, ['id'], [$field]);
            }

            DB::connection($typeDB)->commit();
        } catch (Exception | ErrorException $e) {
            DB::connection($typeDB)->rollback();
            throw $e;
        }
    }

    private function createQueryString(string $entity, object &$data)
    {
        foreach ($data as $key => $value) {
            if ($value === null || $value === "") {
                $data->{$key} = '.';
            }
        }

        $str = implode(',', array_keys((array)$data));
        $val = null;
        for ($i = 0, $length = count((array)$data); $i < $length; $i++) {
            if ($length - 1 == $i) {
                $val = $val . '?';
                break;
            }

            $val = $val . '?,';
        }
        //---
        return "insert into {$entity} ({$str}) values ($val)";
    }
}
