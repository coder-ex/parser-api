<?php

namespace App\Console\Commands\Export;

use App\Services\Export\SystemService;
use Illuminate\Console\Command;

class SystemCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:service
                            {project_id? : id кабинета или проекта}
                            {name? : name проекта}
                            {key? : ключь доступа к проекту}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создание доступов к сервисам';

    /**
     * Создать новый экземпляр команды
     */
    public function __construct(
        private SystemService $systemService = new SystemService(),
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
        $project_id = $this->ask('project_id');
        do {
            if (is_null($project_id)) {
                $this->warn('параметр [project_id] не задан / project_id обязательный !!');
                $this->newLine();
            } else {
                $res = $this->systemService->getProjects(env('TYPE_DB'), $project_id);
                if (count($res) > 0) {
                    $this->error("[ проект: {$project_id} ] как сервис в БД уже есть");
                    $this->newLine();
                    return 1;
                }

                break;
            }

            $project_id = $this->ask('project_id');
        } while (true);

        $name = $this->ask('name');
        do {
            if (is_null($name)) {
                $this->error('параметр [name] не задан / ИМЯ обязательное !!');
                $this->newLine();
            } else {
                break;
            }

            $name = $this->ask('name');
        } while (true);

        $secret = $this->ask('key');
        do {
            if (is_null($secret)) {
                $this->error('параметр [key] не задан / key обязательный !!');
                $this->newLine();
            } else {
                break;
            }

            $secret = $this->ask('key');
        } while (true);

        $this->info('процесс ввода параметров завершен');

        // запись параметров в БД
        $param[] = [
            'project_id' => $project_id,
            'name' => strtolower($name),  // переведем в нижний регистр весь name
            'secret' => $secret,
        ];

        $this->systemService->addService(env('TYPE_DB'), $param);
        $service = $this->systemService->getServiceOne(env('TYPE_DB'), $secret);

        //--- вывод записанных данных
        $this->table(
            [
                'project_id', 'name', 'secret'
            ],
            $service->get()->toArray()
        );

        //---
        return 0;
    }
}
