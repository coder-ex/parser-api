### комментарии по ТЗ
* API для выгрузки:
>
    Roistat (в процессе разработки)
    https://roistat.api-docs.io/v1/analitika/project-analytics-list-orders
    https://roistat.api-docs.io/v1/analitika/project-analytics-data
    https://roistat.api-docs.io/v1/proekty/user-projects
    https://roistat.api-docs.io/v1/reklamnye-kanaly/project-analytics-source-list - удалено за ненадобностью
    https://roistat.api-docs.io/v1/vizity/project-site-visit-list
    https://roistat.api-docs.io/v1/zakazy/project-integration-order-list - новый метод т.к. list-orders устаревший, на будущее !!
    
    Wildberies
    ...
    Ozon
    ...

* в ТЗ не указано, но по идее нужно было реализовать аутентификацию и авторизацию
* реализован REST API на back-end
>
    REST API необходим т.к. из комманд загрузка данных падает, если большой
    объем получаемых данных, вероятно связано с настройками процессов на VPS сервере
* все указанные в задании эндпоинты реализованы так же через commands
>
    roistat:service           Создание доступов к сервисам
    roistat:task              Создание задач для планировщика
    roistat:data              выдача ответов по методу API - data
    roistat:list-integration  выдача ответов по методу API - /project/integration/order/list
    roistat:list-orders       выдача ответов по методу API - list-orders
    roistat:projects          выдача ответов по методу API - projects
    roistat:visit-list        выдача ответов по методу API - visit-list

    все комманды можно просмотреть через php artisan c roistat:xxx

* данные выгружаются:
>
    на удаленный сервер для аналитиков
    локальная БД и файлы

>
    настройка выгрузки по БД находится в .env файле, параметр
    TYPE_DB=(mysql vs postgres)

* так же необходимо добавить вторую БД вместо Postgres

    DB_CONNECTION_SECOND=pgsql
    DB_HOST_SECOND=192.168.222.4
    DB_PORT_SECOND=5432
    DB_DATABASE_SECOND=school
    DB_USERNAME_SECOND=admin
    DB_PASSWORD_SECOND=123456

* коллекция REST эндпоинтов Postman не создавалась
* создана сервисная функция (доступна из консоли tinker) для получения дампа таблиц, дампы выгружаются в файл в storage/app

>
    данные сохраняются в файлы в app/storage/app, имена файлов по моделям:
    __name__.json - если данные получены, они тут
    __name__-success.json - если запись в БД прошла
    __name__-error.json - если какая то ошибка, выгружается message исключения
    - в имена файлов контроля выгрузки дописывается текущая дата
    - в имена файлов дампа таблиц длописывается указанная дата

* настройки получения данных по магазину, находятся в таблицах
>
    export_services - указывается токен магазина и опционально название магазина
    - если название магазина не указать, то сформируется автоматически
    export_tasks - указываются данные по заданиям планировщика для каждого эндпоинта

    данные таблицы заполняются автоматически при предварительной настройке через комманды (см. выше)

>
    - запуск планировщика php artisan schedule:work (либо настроить по мануалу Laravel/Планировщик)

---

> !! выявленные проблемы по поставленной задаче !!

---
