<?php

namespace App\Services\MariaBA\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class IncomeEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->bigInteger('incomeId');         // номер поставки
        $table->string('number')->nullable();   // номер УПД
        $table->timestamp('date')->nullable();  // дата поступления
        $table->timestamp('lastChangeDate', 3)->index();// дата и время обновления информации в сервисе : "2022-07-01T07:42:01.793"
        $table->string('supplierArticle')->index();     // ваш артикул
        $table->string('techSize');             // размер
        $table->string('barcode');              // штрих-код
        $table->bigInteger('quantity');         // кол-во
        $table->float('totalPrice');            // цена из УПД
        $table->date('dateClose')->nullable();  // дата принятия (закрытия) у нас
        $table->string('warehouseName');        // название склада
        $table->bigInteger('nmId');             // Код WB
        $table->string('status');               // Текущий статус поставки
        $table->date('dag_date')->nullable();

    }
}
