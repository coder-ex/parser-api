<?php

namespace App\Repositories\Export;

use App\Models\Export\ExportService;
use App\Repositories\Base\Repository;
use Exception;
use Illuminate\Support\Facades\DB;

class ServiceRepository extends Repository
{
    public function getServiceOne(string $typeDB, string $secret): ExportService|null
    {
        return ExportService::on($typeDB)
            ->where('secret', '=', $secret)
            ->first();
    }

    public function getProjects(string $typeDB, string $projectId): object|null
    {
        return ExportService::on($typeDB)
            ->where('project_id', '=', $projectId)
            ->get();
    }

    public function getSecrets(string $typeDB, string $secret): object|null
    {
        return ExportService::on($typeDB)
            ->where('secret', '=', $secret)
            ->get();
    }

    /**
     * чтение через выбранное соединение с БД
     *
     * @param string $typeDB
     * @param string $projectId
     * @param string $task
     * @return ExportService|null
     */
    public function getTaskOne(string $typeDB, string $projectId, string $task): ExportService|null
    {
        return ExportService::on($typeDB)->where('project_id', '=', $projectId)->join('export_tasks', function ($query) use ($task) {
            $query->on('export_services.id', '=', 'export_tasks.service_id')->where('task', '=', $task);
        })->first();
    }

    protected function createData(string $class, string $typeDB, string $task, array $data): ExportService
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
