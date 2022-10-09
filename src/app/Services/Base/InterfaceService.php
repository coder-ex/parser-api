<?php

namespace App\Services\Base;

interface InterfaceService
{
    /**
     * метод запуска основного потока отработки по получению данных из API
     *
     * @param string $table основная таблица в БД для сохранения данных из API
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param string $urlAPI шаблон url по API
     * @param string $project идентификатор проекта
     * @param string $secret ключ к кабинету клиента (key | token и т.д.)
     * @param string $task задача в планировщике
     * @return void
     */
    public function run(string $table, string $typeDB, string $urlAPI, string $project, string $secret, string $task);

    /**
     * создание url API
     *  при реализации метода, использовать принцип подстановки Барбары Лисков ( LSP ):
     *  - новые параметры создавать необязательными
     *  - делать обязательный параметр необязательным
     *
     * @param string $urlAPI
     * @return string
     */
    public function createUrl(string $urlAPI): string;

    /**
     * получение стартовых даты / время для запроса данных в API
     *  при реализации метода предусмотреть следующий порядок:
     *  1. проверка в кеш
     *  2. проверка в БД
     *      - если данных нет в п.1. и 2 то берем дату / время из планировщика
     *      - если данные есть в п.1 и 2, то приоритет имеет БД если в ней данные свежее кеша
     *      - иначе данные берутся на основе кеша
     *
     * @param string $table таблица выгрузки
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param string $project идентификатор проекта
     * @param string $from дата FROM из планировщика
     * @param string $field
     * @param string $timezoneId = 'Europe/Moscow'
     * @return string
     */
    public function getDateFrom(string $table, string $typeDB, string $project, string $from, string $field, string $timezoneId = 'Europe/Moscow'): string;
}
