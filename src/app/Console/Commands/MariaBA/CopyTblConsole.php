<?php

namespace App\Console\Commands\MariaBA;

use App\Repositories\Base\Repository;
use ErrorException;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;

class CopyTblConsole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maria:copy-tbl';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Копирование таблиц';

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
        $typeDB = env('TYPE_DB');

        $tables = [
            'excise_reports',
            'incomes',
            'orders',
            'sales',
            'sales_reports',
            'stocks'
        ];

        foreach ($tables as $item) {
            $entity = "wb_eremina_{$item}";

            if (!$this->repository->isTable('mysql_2', $entity)) {
                continue;
            }

            try {
                $by = $this->byField($item);
                DB::connection('mysql_2')->table($entity)->orderBy($by)->chunk(1000, function ($chunk) use ($item) {
                    $entity = "wb_kulemina_{$item}";

                    foreach ($chunk as $el) {
                        $el->id = Str::uuid();
                    }

                    $this->save('pgsql_2', $entity, $chunk);
                });
            } catch (Exception $e) {
                outMsg('copy-db', 'error.', $e->getMessage(), true);
                continue;
            }
        }
        //---
        return 0;
    }

    private function byField($task)
    {
        if ($task === 'sales_reports') {                                 // wildberies
            return 'rr_dt';
        } elseif ($task === 'incomes' || $task === 'orders' || $task === 'sales' || $task === 'stocks') {
            return 'lastChangeDate';
        } elseif ($task === 'excise_reports') {
            return 'date';
        }
        //---
        return null;
    }

    private function save($typeDB, $entity, array|object $data)
    {
        DB::connection($typeDB)->beginTransaction();
        try {
            foreach ($data as $value) {
                DB::connection($typeDB)->table($entity)->insert(($value instanceof stdClass) ? (array)$value : $value);
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
