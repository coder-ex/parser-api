<?php

namespace App\Services\Base;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

abstract class BaseEntity
{
    /** создание таблицы */
    abstract protected function addColumn(Blueprint $table);

    protected string $entity;       // сущность (таблица в БД)

    public function __construct(
        protected string $typeDB,       // тип БД определенной в .env
        protected string $name,         // name проекта по Export
        protected string $nameAPI,      // название API по ТЗ
        string $tplTbl,                 // шаблон таблицы
        protected string $tplFK = '', // шаблон связанной таблицы
    ) {
        $last_symb = null;

        if (
            $tplTbl !== 'statistics_daily' &&
            $tplTbl !== 'data' &&
            $tplTbl !== 'campaign' &&
            $tplTbl !== 'campaign_media' &&
            mb_substr($tplTbl, mb_strlen($tplTbl) - 1, 1) !== 's'
        ) {
            $last_symb = 's';
        } else {
            $last_symb = '';
        }

        $this->entity = "{$this->nameAPI}_{$this->name}_{$tplTbl}" . $last_symb;
    }

    public function up()
    {
        if (!$this->isTable()) {
            Schema::connection($this->typeDB)->create($this->entity, function (Blueprint $table) {
                $this->addColumn($table);
            });
        } else {
            Schema::connection($this->typeDB)->table($this->entity, function (Blueprint $table) {
                $this->newColumn($table);
            });
        }
    }

    /**
     * удаление таблицы
     *
     * @return void
     */
    public function down()
    {
        if ($this->isTable()) {
            Schema::connection($this->typeDB)->dropIfExists($this->entity);
        }
    }

    /**
     * получить имя таблицы
     *
     * @return string
     */
    public function getTable()
    {
        if (!$this->isTable()) {
            $this->up();
        }
        //---
        return $this->entity;
    }

    /**
     * добавление полей в таблицу в процессе рефакторинга/доработки
     *
     * @param Blueprint $table
     * @return void
     */
    protected function newColumn(Blueprint $table)
    {
        // добавляем те поля которые хотим добавить в процессе рефакторинга/доработки
        // if (!$this->isColumn('id')) $table->id();
    }

    /**
     * проверка на существование таблицы в БД
     *
     * @return boolean
     */
    protected function isTable()
    {
        return Schema::connection($this->typeDB)->hasTable($this->entity) ? true : false;
    }

    /**
     * проверка на существование полей в таблице
     *
     * @param string $column
     * @return boolean
     */
    protected function isColumn(string $column): bool
    {
        if ($this->isTable()) {
            return Schema::connection($this->typeDB)->hasColumn($this->entity, $column) ? true : false;
        }
        //---
        return false;
    }
}
