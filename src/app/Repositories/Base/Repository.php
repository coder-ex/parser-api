<?php

namespace App\Repositories\Base;

use ErrorException;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Repository
{
    protected $debug;

    public function __construct()
    {
        $this->debug = env('APP_DEBUG');
    }

    /**
     * запись массива через выбранное соединение с БД
     *
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param string $task имя задачи
     * @param array $data массив данных для записи в БД
     * @return array массив записанных данных
     */
    public function saveComby(string $class, string $typeDB, string $task, array $data)
    {
        $res = [];

        DB::connection($typeDB)->beginTransaction();
        try {
            foreach ($data as $key => $value) {
                $res[] = $class::on($typeDB)->create($value);
                $res[$key]['products'] = $value['products'];
            }

            DB::connection($typeDB)->commit();
        } catch (Exception $e) {
            outMsg($task, 'error.', $e->getMessage(), $this->debug);
            //Log::error($e->getMessage());
            $res = null;
            DB::connection($typeDB)->rollback();
        }
        //---
        return $res;
    }

    /**
     * запись массива через выбранное соединение с БД
     *
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param string $task имя задачи
     * @param array $data массив данных для записи в БД
     * @return void
     */
    public function save(string $class, string $typeDB, array $data)
    {
        DB::connection($typeDB)->beginTransaction();
        try {
            foreach ($data as $value) {
                $class::on($typeDB)->create($value);
            }

            DB::connection($typeDB)->commit();
        } catch (Exception $e) {
            DB::connection($typeDB)->rollback();
            throw $e;
        }
    }

    /**
     * запись массива через выбранное соединение с БД
     *
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param string $task имя задачи
     * @param array $item элемент для записи в БД
     * @return array массив записанных данных
     */
    public function saveOne(string $class, string $typeDB, string $task, array $item)
    {
        $res = null;

        DB::connection($typeDB)->beginTransaction();
        try {
            $res = $class::on($typeDB)->create($item);
            DB::connection($typeDB)->commit();
        } catch (Exception $e) {
            outMsg($task, 'error.', $e->getMessage(), $this->debug);
            //Log::error($e->getMessage());
            $res = null;
            DB::connection($typeDB)->rollback();
        }
        //---
        return $res;
    }

    /**
     * обновление элемента через выбранное соединение с БД
     *
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param string $task имя задачи
     * @param array $item элемент для записи в БД
     * @return array массив записанных данных
     */
    public function updateOne(string $class, string $typeDB, string $task, array $item)
    {
        $res = null;

        DB::connection($typeDB)->beginTransaction();
        try {
            $res = $class::on($typeDB)->update($item);
            DB::connection($typeDB)->commit();
        } catch (Exception $e) {
            outMsg($task, 'error.', $e->getMessage(), $this->debug);
            //Log::error($e->getMessage());
            DB::connection($typeDB)->rollback();
        }
        //---
        return $res;
    }

    /**
     * обновление массива через выбранное соединение с БД
     *
     * @param string $class класс модели
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param array $data массив для записи в БД
     * @return void
     */
    public function update(string $class, string $typeDB, array $data)
    {
        DB::connection($typeDB)->beginTransaction();
        try {
            foreach ($data as $item) {
                $class::on($typeDB)->update($item);
            }
            DB::connection($typeDB)->commit();
        } catch (Exception | ErrorException $e) {
            DB::connection($typeDB)->rollback();
            throw $e;
        }
    }

    /**
     * запись в таблицы с query
     *
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param string $query строка запроса
     * @param array $data массив для записи в БД
     * @return void
     */
    public function insertQuery(string $typeDB, string $query, array $data)
    {
        DB::connection($typeDB)->beginTransaction();
        try {
            foreach ($data as $value) {
                DB::connection($typeDB)->insert($query, $value);
            }

            DB::connection($typeDB)->commit();
        } catch (Exception | ErrorException $e) {
            DB::connection($typeDB)->rollback();
            throw $e;
        }
    }

    /**
     * запись в таблицы
     *
     * @param string $table таблица выгрузки
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param array $data массив для записи в БД
     * @return void
     */
    public function insertTable(string $table, string $typeDB, array $data)
    {
        DB::connection($typeDB)->beginTransaction();
        try {
            foreach ($data as $value) {
                $value['id'] = Str::uuid();
                DB::connection($typeDB)->table($table)->insert($value);
            }

            DB::connection($typeDB)->commit();
        } catch (Exception | ErrorException $e) {
            DB::connection($typeDB)->rollback();
            throw $e;
        }
    }

    /**
     * проверка на существование таблицы в БД
     *
     * @param string $typeDB
     * @param string $table
     * @return boolean
     */
    public function isTable(string $typeDB, string $table): bool
    {
        return Schema::connection($typeDB)->hasTable($table) ? true : false;
    }
}
