<?php

namespace App\Services\MariaBA\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class OrderEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->BigInteger('number')->nullable();   // из старой выгрузки
        $table->timestamp('date');                  // дата заказа : "2021-09-01T14:17:37"
        $table->timestamp('lastChangeDate')->index();   // дата и время обновления информации в сервисе : "2021-09-01T14:17:37"
        $table->string('supplierArticle')->index(); // ваш артикул
        $table->string('techSize');                 // размер
        $table->string('barcode');                  // штрих-код
        $table->BigInteger('quantity')->nullable(); // из старой выгрузки
        $table->float('totalPrice');                // цена до согласованной скидки/промо/спп
        $table->BigInteger('discountPercent');      // согласованный итоговый дисконт
        $table->string('warehouseName');            // склад отгрузки
        $table->string('oblast');                   // область
        $table->float('incomeID')->nullable();      // номер поставки
        $table->BigInteger('odid');                 // уникальный идентификатор позиции заказа
        $table->BigInteger('nmId');                 // Код WB
        $table->string('subject')->index();         // предмет
        $table->string('category')->index();        // категория
        $table->string('brand')->index();           // бренд
        $table->boolean('isCancel');                // Отмена заказа. 1 – заказ отменен до оплаты
        $table->date('cancel_dt')->nullable();      // "0001-01-01T00:00:00"
        $table->string('gNumber');                  // номер заказа
        $table->string('sticker');                  // аналогично стикеру, который клеится на товар в процессе сборки
        $table->date('dag_date')->nullable();
        $table->string('srid')->nullable();

    }
}
