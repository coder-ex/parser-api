<?php

namespace App\Console;

use App\Helpers\TypeTask;
use App\Jobs\OZONProcess;
use App\Jobs\RoistatProcess;
use App\Jobs\WBProcess;
use App\Models\Export\ExportService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\Export\SystemCommand::class,
        Commands\Export\TaskCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $this->wbSchedule($schedule);
        $this->roistatSchedule($schedule);
        $this->ozonSchedule($schedule);
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        $this->load(__DIR__ . '/Commands/WB');
        $this->load(__DIR__ . '/Commands/Roistat');
        $this->load(__DIR__ . '/Commands/Ozon');

        require base_path('routes/console.php');
    }

    /**
     * Получить часовой пояс, который должен использоваться по умолчанию для запланированных событий.
     *
     * @return \DateTimeZone|string|null
     */
    protected function scheduleTimezone()
    {
        return 'Europe/Moscow';
    }

    private function roistatSchedule(Schedule $schedule)
    {
        $typeDB = env('TYPE_DB');
        $stores = ExportService::on(env('TYPE_DB'))->get()->all();
        foreach ($stores as $store) {
            $tasks = $store->tasks;

            for ($i = 0, $size = count($tasks); $i < $size; $i++) {
                if (mb_substr($tasks[$i]->start_time, 0, 5) === '00:00') continue;

                $table = $tasks[$i]->table;     // getObjectClass($tasks[$i]->task, $store->name);

                match ($tasks[$i]->task) {
                    //--- для осуществления последовательности загрузки: data -> list_integrations
                    TypeTask::Data->value => [
                        $schedule->call(
                            function () use ($tasks, $i, $store, $typeDB, $table,) {
                                $tbl_arr = [['task' => $tasks[0]->task, 'value' => $tasks[0]->table], ['task' => $tasks[1]->task, 'value' => $tasks[1]->table]];
                                RoistatProcess::dispatchAfterResponse($tbl_arr, $typeDB, $tasks[$i]->url, $store->project_id, $store->secret, $tasks[$i]->task, $tasks[$i]->dateFrom, json_decode($tasks[$i]['extended_fields'])->limit);
                            }
                        )->dailyAt($tasks[$i]->start_time) //->everySixHours()
                    ],
                    // TypeTask::Data->value => [
                    //     $schedule->call(
                    //         function () use ($tasks, $i, $store, $typeDB, $table) {
                    //             RoistatProcess::dispatchAfterResponse($table, $typeDB, $tasks[$i]->url, $store->project_id, $store->secret, $tasks[$i]->task, $tasks[$i]->dateFrom);
                    //         }
                    //     )->dailyAt($tasks[$i]->start_time) //->everySixHours()
                    // ],
                    // TypeTask::ListIntegration->value => [
                    //     $schedule->call(
                    //         function () use ($tasks, $i, $store, $typeDB, $table,) {
                    //             RoistatProcess::dispatchAfterResponse($table, $typeDB, $tasks[$i]->url, $store->project_id, $store->secret, $tasks[$i]->task, $tasks[$i]->dateFrom, json_decode($tasks[$i]['extended_fields'])->limit);
                    //         }
                    //     )->dailyAt($tasks[$i]->start_time) //->everySixHours()
                    // ],
                    // TypeTask::VisitList->value => [
                    //     $schedule->call(
                    //         function () use ($tasks, $i, $store, $typeDB, $table,) {
                    //             RoistatProcess::dispatchAfterResponse($table, $typeDB, $tasks[$i]->url, $store->project_id, $store->secret, $tasks[$i]->task, $tasks[$i]->dateFrom, json_decode($tasks[$i]['extended_fields'])->limit);
                    //         }
                    //     )->dailyAt($tasks[$i]->start_time) //->everySixHours()
                    // ],
                    default => null,
                };
            }
        }
    }

    private function ozonSchedule(Schedule $schedule)
    {
        $typeDB = env('TYPE_DB');
        $stores = ExportService::on(env('TYPE_DB'))->get()->all();
        foreach ($stores as $store) {
            $tasks = $store->tasks;

            for ($i = 0, $size = count($tasks); $i < $size; $i++) {
                if (mb_substr($tasks[$i]->start_time, 0, 5) === '00:00') continue;

                $table = $tasks[$i]->table;     // getObjectClass($tasks[$i]->task, $store->name);

                match ($tasks[$i]->task) {
                    TypeTask::StockWarehouses->value => [
                        $schedule->call(
                            function () use ($tasks, $i, $store, $typeDB, $table,) {
                                OZONProcess::dispatchAfterResponse($table, $typeDB, $tasks[$i]->url, $store->project_id, $store->secret, $tasks[$i]->task, '', json_decode($tasks[$i]['extended_fields'])->limit);
                            }
                        )->dailyAt($tasks[$i]->start_time) //->everySixHours()
                    ],
                    TypeTask::FboList->value => [
                        $schedule->call(
                            function () use ($tasks, $i, $store, $typeDB, $table) {
                                OZONProcess::dispatchAfterResponse($table, $typeDB, $tasks[$i]->url, $store->project_id, $store->secret, $tasks[$i]->task, $tasks[$i]->dateFrom, json_decode($tasks[$i]['extended_fields'])->limit);
                            }
                        )->dailyAt($tasks[$i]->start_time) //->everySixHours()
                    ],
                    TypeTask::Campaign->value => [
                        $schedule->call(
                            function () use ($tasks, $i, $store, $typeDB, $table) {
                                OZONProcess::dispatchAfterResponse($table, $typeDB, $tasks[$i]->url, $store->project_id, $store->secret, $tasks[$i]->task);
                            }
                        )->dailyAt($tasks[$i]->start_time) //->everySixHours()
                    ],
                    TypeTask::StatDaily->value => [
                        $schedule->call(
                            function () use ($tasks, $i, $store, $typeDB, $table) {
                                OZONProcess::dispatchAfterResponse($table, $typeDB, $tasks[$i]->url, $store->project_id, $store->secret, $tasks[$i]->task, $tasks[$i]->dateFrom);
                            }
                        )->dailyAt($tasks[$i]->start_time) //->everySixHours()
                    ],
                    TypeTask::StatMediaCampaign->value => [
                        $schedule->call(
                            function () use ($tasks, $i, $store, $typeDB, $table) {
                                OZONProcess::dispatchAfterResponse($table, $typeDB, $tasks[$i]->url, $store->project_id, $store->secret, $tasks[$i]->task, $tasks[$i]->dateFrom);
                            }
                        )->dailyAt($tasks[$i]->start_time) //->everySixHours()
                    ],
                    TypeTask::StatFoodCampaign->value => [
                        $schedule->call(
                            function () use ($tasks, $i, $store, $typeDB, $table) {
                                OZONProcess::dispatchAfterResponse($table, $typeDB, $tasks[$i]->url, $store->project_id, $store->secret, $tasks[$i]->task, $tasks[$i]->dateFrom);
                            }
                        )->dailyAt($tasks[$i]->start_time) //->everySixHours()
                    ],
                    TypeTask::StatExpenseCampaign->value => [
                        $schedule->call(
                            function () use ($tasks, $i, $store, $typeDB, $table) {
                                OZONProcess::dispatchAfterResponse($table, $typeDB, $tasks[$i]->url, $store->project_id, $store->secret, $tasks[$i]->task, $tasks[$i]->dateFrom);
                            }
                        )->dailyAt($tasks[$i]->start_time) //->everySixHours()
                    ],

                    default => null,
                };
            }
        }
    }

    private function wbSchedule(Schedule $schedule)
    {
        $typeDB = env('TYPE_DB');
        $stores = ExportService::on(env('TYPE_DB'))->get()->all();
        foreach ($stores as $store) {
            $tasks = $store->tasks;

            for ($i = 0, $size = count($tasks); $i < $size; $i++) {
                if (mb_substr($tasks[$i]->start_time, 0, 5) === '00:00') continue;

                $table = $tasks[$i]->table;     // getObjectClass($tasks[$i]->task, $store->name);

                match ($tasks[$i]->task) {
                    TypeTask::Incomes->value => [
                        $schedule->call(
                            function () use ($tasks, $i, $store, $typeDB, $table,) {
                                WBProcess::dispatchAfterResponse($table, $typeDB, $tasks[$i]->url, $store->project_id, $store->secret, $tasks[$i]->task, $tasks[$i]->dateFrom);
                            }
                        )->dailyAt($tasks[$i]->start_time) //->everySixHours()
                    ],
                    TypeTask::Stocks->value => [
                        $schedule->call(
                            function () use ($tasks, $i, $store, $typeDB, $table) {
                                WBProcess::dispatchAfterResponse($table, $typeDB, $tasks[$i]->url, $store->project_id, $store->secret, $tasks[$i]->task, $tasks[$i]->dateFrom);
                            }
                        )->dailyAt($tasks[$i]->start_time) //->everySixHours()
                    ],
                    TypeTask::Orders->value => [
                        $schedule->call(
                            function () use ($tasks, $i, $store, $typeDB, $table) {
                                WBProcess::dispatchAfterResponse($table, $typeDB, $tasks[$i]->url, $store->project_id, $store->secret, $tasks[$i]->task, $tasks[$i]->dateFrom, json_decode($tasks[$i]['extended_fields'])->flag);
                            }
                        )->dailyAt($tasks[$i]->start_time) //->everySixHours()
                    ],
                    TypeTask::Sales->value => [
                        $schedule->call(
                            function () use ($tasks, $i, $store, $typeDB, $table) {
                                WBProcess::dispatchAfterResponse($table, $typeDB, $tasks[$i]->url, $store->project_id, $store->secret, $tasks[$i]->task, $tasks[$i]->dateFrom, json_decode($tasks[$i]['extended_fields'])->flag);
                            }
                        )->dailyAt($tasks[$i]->start_time) //->everySixHours()
                    ],
                    TypeTask::SalesReports->value => [
                        $schedule->call(
                            function () use ($tasks, $i, $store, $typeDB, $table) {
                                WBProcess::dispatchAfterResponse($table, $typeDB, $tasks[$i]->url, $store->project_id, $store->secret, $tasks[$i]->task, $tasks[$i]->dateFrom, json_decode($tasks[$i]['extended_fields'])->flag, json_decode($tasks[$i]['extended_fields'])->limit, date('Y-m-d'));
                            }
                        )->dailyAt($tasks[$i]->start_time) //->everySixHours()
                    ],
                    TypeTask::ExciseReports->value => [
                        $schedule->call(
                            function () use ($tasks, $i, $store, $typeDB, $table) {
                                WBProcess::dispatchAfterResponse($table, $typeDB, $tasks[$i]->url, $store->project_id, $store->secret, $tasks[$i]->task, $tasks[$i]->dateFrom);
                            }
                        )->dailyAt($tasks[$i]->start_time) //->everySixHours()
                    ],
                    default => null,
                };
            }
        }
    }
}
