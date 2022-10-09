<?php

namespace App\Console\Commands;

use App\Repositories\Base\Repository;
use App\Services\Libs\BackUpFile;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Backup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BuckUpTableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:algo';

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
        (new BackUpFile())->pack('ozon_aqua_stock_warehouses', 'offer_id', 'mysql');

        // $filePath = date('Y-m-d') . '-ozon_aqua_stock_warehouses.zip';
        // if (Storage::exists($filePath)) {
        //     $data = (new BackUpFile())->unpack('ozon_aqua_stock_warehouses', typeDB: 'mysql', date: '2022-10-04');
        //     $dataDB = [];
        //     foreach ($data as $item) {
        //         $dataDB[] = (array)$item;
        //     }

        //     (new Repository)->insertTable('ozon_aqua_stock_warehouses', 'mysql', $dataDB);
        // }

        //---
        return 0;
    }
}
