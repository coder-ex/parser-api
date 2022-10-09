<?php

namespace App\Services\Ozon\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class StatGetReportEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->string('project_id');
        $table->date('published_at')->nullable();               // дата получения среза
        $table->date('date')->nullable();                       // Дата
        $table->integer('order_id')->nullable();                // ID заказа
        $table->integer('order_number')->nullable();            // Номер заказа
        $table->integer('ozon_id')->nullable();                 // Ozon ID
        $table->integer('ozon_id_advert_product')->nullable();  // Ozon ID рекламируемого товара
        $table->string('article')->nullable();                  // Артикул
        $table->string('name', 1000)->nullable();               // Наименовние
        $table->integer('quantity')->nullable();                // Количество
        $table->double('sale_price', 15, 2)->nullable();        // Цена продажи
        $table->double('cost', 15, 2)->nullable();              // Стоимость руб
        $table->integer('interest_rate')->nullable();           // Ставка %
        $table->double('rate_rubles', 15, 2)->nullable();       // Ставка руб
        $table->double('expense_rubles', 15, 2)->nullable();    // Расход
    }
}
