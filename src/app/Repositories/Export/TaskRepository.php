<?php

namespace App\Repositories\Export;

use App\Models\Export\ExportTask;
use App\Repositories\Base\Repository;
use Exception;
use Illuminate\Support\Facades\DB;

class TaskRepository extends Repository
{
    protected function createData(string $class, string $typeDB, string $task, array $data): ExportTask
    {
        DB::connection($typeDB)->beginTransaction();

        $res = null;

        try {
            $res = $class::on($typeDB)->create($data);

            DB::connection($typeDB)->commit();
        } catch (Exception $e) {
            outMsg($task, 'error.', $e->getMessage(), $this->debug);

            DB::connection($typeDB)->rollback();
        }
        //---
        return $res;
    }
}