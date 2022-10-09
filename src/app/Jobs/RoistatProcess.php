<?php

namespace App\Jobs;

use App\Helpers\TypeTask;
use App\Services\Roistat\DataService;
use App\Services\Roistat\ListIntegrationService;
use App\Services\Roistat\UpOrderLIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RoistatProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    //private string $dateTo;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private string|array $table,
        private string $typeBD,
        private string $urlAPI,
        private string $project,
        private string $secret,
        private string $task,
        private string $dateFrom = '',
        private int $limit = 10000,
        private string $dateTo = '',
    ) {
        if ($this->dateTo === '') {
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
            //--- для осуществления последовательности загрузки: data -> list_integrations
            TypeTask::Data->value => [
                (new DataService())->run($this->getTbl($this->table, 'data'), $this->typeBD, $this->urlAPI, $this->project, $this->secret, /*$this->task*/'data', $this->dateFrom, $this->dateTo),
                (new ListIntegrationService())->run($this->getTbl($this->table, 'list-integration'), $this->typeBD, $this->urlAPI, $this->project, $this->secret, /*$this->task*/'list-integration', $this->dateFrom, $this->dateTo, $this->limit),
                //(new UpOrderLIService())->run($this->getTbl($this->table, 'list-integration'), $this->typeBD, $this->urlAPI, $this->project, $this->secret),
            ],
        };
    }

    private function getTbl(array $tables, string $task) {
        for($i = 0, $length = count($tables); $i < $length; $i++) {
            if($tables[$i]['task'] === $task) return $tables[$i]['value'];
        }
    }
}
