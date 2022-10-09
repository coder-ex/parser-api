<?php

namespace App\Services\Export;

use App\Repositories\Export\JournalRepository;
use App\Services\Export\Entities\JournalEntity;

class JournalService
{
    private $journal = [];

    public function __construct(
        private string $typeDB,
        private string $project,
        private string $task,
        private string $entity = 'export_journals',
        private JournalRepository $repository = new JournalRepository()
    ) {
        date_default_timezone_set('Europe/Moscow');

        $entity = new JournalEntity($this->typeDB, $this->entity);
        $entity->up();
        unset($entity);
    }

    public function __destruct()
    {
        unset($this->journal);
    }

    /**
     * старт задачи
     *
     * @return void
     */
    public function startTask()
    {
        $time = date('Y-m-d H:i:s');

        //--- создаем объект записи в журнал
        $this->journal = [
            'start_task' => $time,
            'stop_task' => '1970-01-01 03:00:00',
            'task_flag' => 'START',
            'description_flag' => json_encode(
                [
                    'start' => '[ ' . $time . ' ]: старт задачи [ ' . $this->task . ' ] по проекту [ ' . $this->project . ' ]'
                ]
            ),
            'name_task' => $this->task,
            'project_id' => $this->project
        ];

        $this->repository->insertTable($this->entity, $this->typeDB, [$this->journal]);                         // записываем объект в журнал
        $this->journal['id'] = $this->repository->isTask($this->entity, $this->typeDB, $this->journal)?->id;    // добавляем id записи
    }

    /**
     * стоп задачи
     *
     * @param string $flag
     * @param string $msg
     * @return void
     */
    public function upTask(string $flag, string $msg = null)
    {
        date_default_timezone_set('Europe/Moscow');
        $time = date('Y-m-d H:i:s');

        //--- дополняем объект записи в журнал
        // $this->journal['start_task'] = $this->journal['start_task'];
        $this->journal['stop_task'] = $time;
        $this->journal['task_flag'] = $this->journal['task_flag'] . ' | ' . $flag;
        $this->journal['description_flag'] = json_encode(
            [
                'start' => json_decode($this->journal['description_flag'], true)['start'],
                'stop' => '[ ' . $time . ' ]: стоп задачи [ ' . $this->task . ' ] по проекту [ ' . $this->project . ' ]',
                'success' => $msg ?? null
            ],
            JSON_UNESCAPED_UNICODE
        );
        // $this->journal['name_task'] = $this->task;
        // $this->journal['project_id'] = $this->projec;

        $this->repository->updateTable($this->entity, $this->typeDB, $this->journal);   // внесем изменения в журнад
    }
}
