<?php

namespace App\Jobs;

use App\Helpers\TypeTask;
use App\Services\Ozon\CampaignService;
use App\Services\Ozon\FboListService;
use App\Services\Ozon\RefWarehouseService;
use App\Services\Ozon\ReportStockService;
use App\Services\Ozon\StatDailyService;
use App\Services\Ozon\StatExpenseCampaignService;
use App\Services\Ozon\StatFoodCampaignService;
use App\Services\Ozon\StatMediaCampaignService;
use App\Services\Ozon\StockWarehouseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OZONProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private string $table,
        private string $typeDB,
        private string $urlAPI,
        private string $project,
        private string $secret,
        private string $task,
        private string $dateFrom = '',
        private int $limit = 10000,
        private string $dateTo = '',
    ) {
        if ($this->dateTo === '') {
            $this->dateTo = formatDateTo(date('Y-m-d H:i:s'), 'Y-m-d\TH:i:s.v\Z', 'UTC');
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
            TypeTask::StockWarehouses->value => (new StockWarehouseService())->run($this->table, $this->typeDB, $this->urlAPI, $this->project, $this->secret, $this->task, $this->limit),
            TypeTask::FboList->value => (new FboListService())->run($this->table, $this->typeDB, $this->urlAPI, $this->project, $this->secret, $this->task, $this->dateFrom, $this->dateTo, $this->limit),
            TypeTask::StatDaily->value => (new StatDailyService())->run($this->table, $this->typeDB, $this->urlAPI, $this->project, $this->secret, $this->task, $this->dateFrom, $this->dateTo),
            TypeTask::Campaign->value => (new CampaignService())->run($this->table, $this->typeDB, $this->urlAPI, $this->project, $this->secret, $this->task),
            TypeTask::StatMediaCampaign->value => (new StatMediaCampaignService())->run($this->table, $this->typeDB, $this->urlAPI, $this->project, $this->secret, $this->task, $this->dateFrom, $this->dateTo),
            TypeTask::StatFoodCampaign->value => (new StatFoodCampaignService())->run($this->table, $this->typeDB, $this->urlAPI, $this->project, $this->secret, $this->task, $this->dateFrom, $this->dateTo),
            TypeTask::StatExpenseCampaign->value => (new StatExpenseCampaignService())->run($this->table, $this->typeDB, $this->urlAPI, $this->project, $this->secret, $this->task, $this->dateFrom, $this->dateTo),
            TypeTask::ReportStocks->value => [
                (new ReportStockService())->run($this->table, $this->typeDB, $this->urlAPI, $this->project, $this->secret, $this->task),
                (new RefWarehouseService())->run('', $this->typeDB, $this->urlAPI, $this->project, $this->secret, $this->task)
            ],
        };
    }
}
