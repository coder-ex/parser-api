<?php

namespace App\Services\Export;

use App\Models\Export\ExportJournal;
use App\Models\Export\ExportService;
use App\Models\Export\ExportTask;
use App\Repositories\Export\ServiceRepository;
use App\Repositories\Export\TaskRepository;

class SystemService
{
    public function __construct(
        private ServiceRepository $serviceRepository = new ServiceRepository(),
        private TaskRepository $taskRepository = new TaskRepository()
    ) {
    }

    /**
     * добавление нового сервиса
     *
     * @param string $typeDB
     * @param string $task
     * @param array $data
     * @return void
     */
    public function addService(string $typeDB, array $data)
    {
        if (is_null($data[0]['project_id'])) {
            $data[0]['project_id'] = 'shop ' . date('Y-m-d H:i');
        }
        //---
        $this->serviceRepository->save(ExportService::class, $typeDB, $data);
    }

    public function addTask(string $typeDB, array $data)
    {
        $this->taskRepository->save(ExportTask::class, $typeDB, $data);
    }

    public function getServiceOne(string $typeDB, string $secret): ExportService|null
    {
        return $this->serviceRepository->getServiceOne($typeDB, $secret);
    }

    public function getProjects(string $typeDB, string $projectId): object|null
    {
        return $this->serviceRepository->getProjects($typeDB, $projectId);
    }

    public function getSecrets(string $typeDB, string $secret): object|null
    {
        return $this->serviceRepository->getSecrets($typeDB, $secret);
    }

    public function getTaskOne(string $typeDB, string $projectId, string $task)
    {
        return $this->serviceRepository->getTaskOne($typeDB, $projectId, $task);
    }
}
