## вводная к ТЗ
    Парсер данных с api: wildberries, ozon, roistat
    ТЗ в каталоге задач ./task
    выгрузки данных из различных источников
    используемые таблицы и схема связей в ./doc/diagram-table_*_.dia
    описание API находится в каталоге задач ./task

### стек разработки и предустановленные пакеты
    docker + docker-compose + Nginx php-fpm + Laravel + Octane + DATABASE + pgadmin
    DATABASE:
        postgres 1: крутится на хосте разработки
        postgres 2: крутится на VPS сервере для тестовых ТЗ и проектов
        mysql: данные сливаем на сервер аналитикам, поэтому в контейнере с php подгружен драйвер для mysql

    Octane + Sdwoole: для повышения производительности (х10) php-fpm
    Nginx: в качестве баллансировщика и для статики (выключен т.к. статика и баллансировка не требуется по ТЗ)
    postgres ( PostgreSQL ) 14.3 ( Debian 14.3-1.pgdg110+1 )
    pgadmin
    pdo_mysql mysqli ( MySQL v)5.7 )
    php v8.1.6
    imagick 3.7.0
    xdebug 3.1.4

### используемые порты, ip
* php: внутренний 9000 / внешний 9010
* xdebug: отладка php 9008 / отладка по web 9003
* octane: внутренний 8000 / внешний 8010
* nginx: внутренни 80, 443 / внешний 80, 443
* postgres: внутренний 5432 / внешний 5432
* pgadmin: внутренний 80 / внешний 8080
* phpmyadmin: внутренний 80 / внешний 3306
* внешняя подсеть: 192.168.222.0/28
* postgres: 192.168.222.4
* локальный домен разработки: school.loc

---
### установка проекта, на сегодня Laravel 9.x
* docker-compose up -d --build
* docker exec -it php-fpm /bin/bash
* composer create-project --prefer-dist laravel/laravel server
* composer require laravel/octane
* настроить конфиги
* php artisan octane:install
* php artisan migrate --path=database/migrations/Export --database=mysql
* php artisan octane:start --server=swoole --host=0.0.0.0 --port=8000
* на VPS сервере все контейнеры кроме php-fpm и DATABASE отключить
* содержание выгрузок находится во внутреннем README.md
* по API стучаться http://ip_addr_vps:8010 (т.к. 8010 прокинут в мир)
