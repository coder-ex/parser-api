<?php

namespace App\Services\Ozon;

use App\Repositories\Ozon\OzonRepository;
use App\Services\Base\BaseService;
use App\Services\Base\InterfaceService;
use App\Services\Exceptions\AuthException;
use App\Services\Export\JournalService;
use App\Services\Ozon\Traits\FetchDataFromAPI;
use ErrorException;
use Exception;

/** список рекламируемых объектов компании / list of advertised objects of the company */
class CampaignObjectService extends BaseService implements InterfaceService
{
    use FetchDataFromAPI;

    /**
     * конструктор
     *
     * @param [type] $repository
     */
    public function __construct(
        private OzonRepository $repository = new OzonRepository(),
    ) {
        parent::__construct();
        date_default_timezone_set('UTC');   // Ozon работает по UTC
    }

    public function __destruct()
    {
        unset($this->repository);
    }

    /**
     * обработка по методу GET /api/client/campaign/48852/objects
     *
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param string $urlAPI шаблон url от API Ozon
     * @param string $project идентификатор проекта
     * @param string $secret
     * @param string $task задача
     * @param \Illuminate\Support\Collection|null $data
     * @return void
     */
    public function run(string $table, string $typeDB, string $urlAPI, string $project, string $secret, string $task, ?\Illuminate\Support\Collection $data=null)
    {
        $journal = new JournalService($typeDB, $project, 'list-adv-objects');
        $journal->startTask();

        try {

            $cntToken = 0;
            $flagToken = true;
            foreach ($data as $campaign) {
                
                //--- получим токен если флаг взведен
                if ($flagToken) {
                    $header = [
                        'Content-Type' => 'application/json',
                        'Accept' => '*/*',
                        'Authorization' => 'Bearer ' . OzonToken::getToken($urlAPI, $project, $secret),
                    ];

                    $flagToken = false;
                }

                $url = $this->createUrl($urlAPI, $campaign->campaign_id);

                $dataDB = [];
                try {
                    foreach ($this->fetchToAPIGet($url, $header) as $unit) {
                        if (count($unit) == 0) continue;

                        $dataDB[] = [
                            'object_campaign_id' => $unit['id'],
                            'fk_campaign_id' => $campaign->id
                        ];
                    }
                } catch(AuthException $e){
                    if($cntToken > 3) {
                        throw new Exception("токен протух > 3 - [ {$e->getMessage()} ]", $e->getCode());
                    }

                    $cntToken++;
                    $flagToken = true;
                    continue;
                }

                foreach (array_chunk($dataDB, 1000) as $unit) {
                    $this->repository->insertTable($table, $typeDB, $unit);
                }
            }
        } catch (Exception | ErrorException $e) {
            $journal->upTask('ERROR', $e->getMessage());
            throw new Exception($e->getMessage(), $e->getCode());
        }

        $journal->upTask('OK');     // обновим запись в журнале

        unset($dataDB);
    }

    public function createUrl(string $urlAPI, ?string $campaignId = ''): string
    {
        return "{$urlAPI}/api/client/campaign/{$campaignId}/objects";
    }
}
