<?php

namespace App\Console\Commands;

use App\Helpers\Enum\TypeTask;
use App\Repositories\Export\ServiceRepository;
use App\Services\Ozon\Entities\StockWarehouseEntity;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ZipArchive;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:algo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестирование различного функционала';

    /**
     * Создать новый экземпляр команды
     */
    public function __construct()
    {
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
        ini_set('memory_limit', -1);

        $typeDB = env('TYPE_DB');

        // создание ключа System V IPC
        $sysId = mt_rand();
        // Создание блока с идентификатором 0xff3 и размером в 100 байт.
        $shm_id = shmop_open($sysId, "c", 0644, 100);
        if (!$shm_id) {
            echo "Невозможно зарезервировать блок в сегменте памяти\n";
        }

        $cnt = 0;
        while (true) {
            try {
                shmop_open($sysId, "n", 0644, 100);
                break;
            } catch (Exception $e) {
                if ($cnt < 3) {
                    $sysId = mt_rand();
                    $cnt++;
                    echo "повтор {$cnt}";
                    continue;
                }

                break;
            }
        }


        // Получение размера блока в разделяемой памяти
        $shm_size = shmop_size($shm_id);
        echo "Участок памяти, размером: " . $shm_size . ", успешно зарезервирован.\n";

        // Проверочная запись некоторой строки в разделяемую память
        $shm_bytes_written = shmop_write($shm_id, "Мой маленький блок", 0);
        if ($shm_bytes_written != strlen("Мой маленький блок")) {
            echo "Не удалось записать весь размер данных\n";
        }

        // Получение хранимой строки из разделяемой памяти
        $my_string = shmop_read($shm_id, 0, $shm_size);
        if (!$my_string) {
            echo "Невозможно прочитать данные из блока памяти\n";
        }
        echo "Данные, размещённые в разделяемой памяти: " . $my_string . "\n";

        // Удаление блока и закрытие сегмента разделяемой памяти
        if (!shmop_delete($shm_id)) {
            echo "Невозможно отметить участок памяти для освобождения.";
        }
        //shmop_close($shm_id);

        //---
        return 0;
    }
}
