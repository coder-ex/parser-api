<?php

namespace App\Console\Commands\MariaBA;

use App\Services\MariaBA\Entities\ExciseReportEntity;
use App\Services\MariaBA\Entities\IncomeEntity;
use App\Services\MariaBA\Entities\OrderEntity;
use App\Services\MariaBA\Entities\SaleEntity;
use App\Services\MariaBA\Entities\SaleReportEntity;
use App\Services\MariaBA\Entities\StockEntity;
use Illuminate\Console\Command;

class CreateTblConsole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maria:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создание таблиц';

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
        $typeDB = 'pgsql_2';

        $entity = new ExciseReportEntity($typeDB, 'kulemina', 'wb', 'excise_reports');
        $entity->up();
        $entity = new IncomeEntity($typeDB, 'kulemina', 'wb', 'incomes');
        $entity->up();
        $entity = new OrderEntity($typeDB, 'kulemina', 'wb', 'orders');
        $entity->up();
        $entity = new SaleEntity($typeDB, 'kulemina', 'wb', 'sales');
        $entity->up();
        $entity = new SaleReportEntity($typeDB, 'kulemina', 'wb', 'sales_reports');
        $entity->up();
        $entity = new StockEntity($typeDB, 'kulemina', 'wb', 'stocks');
        $entity->up();
        //---
        return 0;
    }
}
