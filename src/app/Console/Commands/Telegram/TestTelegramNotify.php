<?php

namespace App\Console\Commands\Telegram;

use App\Models\Export\ExportTask;
use App\Services\Telegram\TelegramNotifierService;
use App\Services\Telegram\TypeNotify;
use Illuminate\Console\Command;

class TestTelegramNotify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:send
                            {token? : token bot`s}
                            {id? : id my account telegram}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'отправка тестового сообщения в Telegram';

    /**
     * Создать новый экземпляр команды
     */
    public function __construct(
        private TelegramNotifierService $service = new TelegramNotifierService()
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
        $id = $this->ask('id');
        if (is_null($id)) {
            $id = env('TELEGRAM_ID');
            if(is_null($id)) {
                $this->warn('задайте параметр [id] в .env');
                $this->newLine();
                return 1;
            }
            $this->newLine();
        }

        $token = $this->ask('token');
        if (is_null($token)) {
            $id = env('TELEGRAM_TOKEN');
            if (is_null($token)) {
                $this->warn('задайте параметр [token] в .env');
                $this->newLine();
                return 1;    
            }
            $this->newLine();
        }

        $tasks = ExportTask::on(env('TYPE_DB'))->get();

        $this->service->init($token, $id);
        //$message = $this->service->addTable($tasks);

        //$this->service->notifyMsg($message);
        $this->service->strToImg('');
        $this->service->notify(TypeNotify::Photo, ['photo' => storage_path("app/public/out.png")]);

        $this->info("Команда завершена");
        $this->newLine();
        //---
        return 0;
    }
}
