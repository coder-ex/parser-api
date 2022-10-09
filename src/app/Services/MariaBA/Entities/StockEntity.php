<?php

namespace App\Services\MariaBA\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class StockEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->timestamp('lastChangeDate', 3)->index();    // дата и время обновления информации в сервисе : "2022-07-01T07:42:01.793"
        $table->string('supplierArticle')->index(); // артикул поставщика
        $table->string('techSize');                 // размер
        $table->string('barcode');                  // штрих-код
        $table->BigInteger('quantity');             // кол-во доступное для продажи
        $table->boolean('isSupply');                // договор поставки
        $table->boolean('isRealization');           // договор реализации
        $table->BigInteger('quantityFull');         // кол-во полное
        $table->BigInteger('quantityNotInOrders');  // кол-во не в заказе
        $table->BigInteger('warehouse');            // уникальный идентификатор склада
        $table->string('warehouseName');            // название склада
        $table->BigInteger('inWayToClient');        // в пути к клиенту (штук)
        $table->BigInteger('inWayFromClient');      // в пути от клиента (штук)
        $table->BigInteger('nmId');                 // код WB
        $table->string('subject')->index();         // предмет
        $table->string('category')->index();        // категория
        $table->BigInteger('daysOnSite');           // кол-во дней на сайте
        $table->string('brand');                    // бренд
        $table->string('SCCode');                   // код контракта
        $table->BigInteger('Price');                // цена из товара
        $table->BigInteger('Discount');             // скидка на товар установленная продавцом
        $table->date('dag_date')->nullable();

    }
}
