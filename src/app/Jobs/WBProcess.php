<?php

namespace App\Jobs;

use App\Helpers\TypeTask;
use App\Services\WB\ExciseGoodsService;
use App\Services\WB\IncomesService;
use App\Services\WB\OrdersService;
use App\Services\WB\ReportSalesService;
use App\Services\WB\SalesService;
use App\Services\WB\StocksService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WBProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    //private string $dateTo;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private string $table,
        private string $typeBD,
        private string $urlAPI,
        private string $project,
        private string $secret,
        private string $task,
        private string $dateFrom,
        private int $flag = 0,
        private int $limit = 10000,
        private string $dateTo = '',
    ) {
        if($this->dateTo === '') {
            $this->dateTo = date('Y-m-d');
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        match ($this->task) {
            TypeTask::Incomes->value => (new IncomesService())->run($this->table, $this->typeBD, $this->urlAPI,$this->project, $this->secret, $this->task, $this->dateFrom),
            TypeTask::Stocks->value => (new StocksService())->run($this->table, $this->typeBD, $this->urlAPI,$this->project, $this->secret, $this->task, $this->dateFrom),
            TypeTask::Orders->value => (new OrdersService())->run($this->table, $this->typeBD, $this->urlAPI,$this->project, $this->secret, $this->task, $this->dateFrom, $this->flag),
            TypeTask::Sales->value => (new SalesService())->run($this->table, $this->typeBD, $this->urlAPI,$this->project, $this->secret, $this->task, $this->dateFrom, $this->flag),
            TypeTask::SalesReports->value => (new ReportSalesService())->run($this->table, $this->typeBD, $this->urlAPI,$this->project, $this->secret, $this->task, $this->dateFrom, $this->dateTo, $this->limit),
            TypeTask::ExciseReports->value => (new ExciseGoodsService())->run($this->table, $this->typeBD, $this->urlAPI,$this->project, $this->secret, $this->task, $this->dateFrom),
        };
    }
}
