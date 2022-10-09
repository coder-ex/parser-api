<?php

namespace App\Console\Commands\Export;

use App\Services\Export\SystemService;
use Illuminate\Console\Command;

class LoadConfigCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:load
                            {file_name? : имя конфигурационного файла}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создание полуавтоматическое создание доступов к сервисам и планировщика заданий';

    /**
     * Создать новый экземпляр команды
     */
    public function __construct(
        private SystemService $service = new SystemService(),
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
        $config = null;
        $file_name = $this->ask('file_name');
        if (is_null($file_name)) {
            $this->warn('параметр [file_name] не задан, выбирается default == config.json');
            $this->newLine();
            $config = loadConfig('config');
        } else {
            $config = loadConfig($file_name);
        }



        //--- создаем service
        $params = [];
        foreach($config as $item) {
            $params[] = [
                "project_id" => $item->project_id,
                "name" => $item->name,
                "secret" => $item->secret,
            ];
        }

        $this->service->addService(env('TYPE_DB'), $param);


        //--- создаем task
    }
}
