<?php

namespace App\Services\MariaBA\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class SaleEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->string('number')->nullable();       // из старой выгрузки
        $table->timestamp('date');                  // дата продажи : "2021-09-01T14:17:37"
        $table->timestamp('lastChangeDate')->index();        // дата и время обновления информации в сервисе : "2021-09-01T14:17:37"
        $table->string('supplierArticle')->index();          // ваш артикул
        $table->string('techSize');                 // размер
        $table->string('barcode');                  // штрих-код
        $table->BigInteger('quantity')->nullable(); // из старой выгрузки
        $table->BigInteger('totalPrice');           // начальная розничная цена товара
        $table->BigInteger('discountPercent');      // согласованная скидка на товар
        $table->boolean('isSupply');                // договор поставки
        $table->boolean('isRealization');           // договор реализации
        $table->float('orderId')->nullable();       // из старой выгрузки
        $table->BigInteger('promoCodeDiscount');    // согласованный промокод
        $table->string('warehouseName');            // склад отгрузки
        $table->string('countryName');              // страна
        $table->string('oblastOkrugName');          // округ
        $table->string('regionName');               // регион
        $table->float('incomeID')->nullable();      // номер поставки
        $table->string('saleID');                   // уникальный идентификатор продажи/возврата (SXXXXXXXXXX — продажа, ...
                                                    //... RXXXXXXXXXX — возврат, DXXXXXXXXXXX — доплата, 'AXXXXXXXXX' – сторно продаж (все значения полей как у
                                                    //... продажи, но поля с суммами и кол-вом с минусом как в возврате). SaleID='BXXXXXXXXX' - сторно
                                                    //... возврата(все значения полей как у возврата, но поля с суммами и кол-вом с плюсом, в
                                                    //... противоположность возврату))
        $table->BigInteger('odid');                 // уникальный идентификатор позиции заказа
        $table->BigInteger('spp');                  // согласованная скидка постоянного покупателя (СПП)
        $table->float('forPay');                    // к перечислению поставщику
        $table->float('finishedPrice');             // фактическая цена из заказа (с учетом всех скидок, включая и от ВБ)
        $table->float('priceWithDisc');             // цена, от которой считается вознаграждение поставщика
        $table->BigInteger('nmId');                 // Код WB
        $table->string('subject', 50)->index();     // предмет
        $table->string('category', 50)->index();    // категория
        $table->string('brand', 50)->index();       // бренд
        $table->BigInteger('IsStorno');
        $table->string('gNumber', 50);              // номер заказа
        $table->string('sticker', 50);              // аналогично стикеру, который клеится на товар в процессе сборки
        $table->date('dag_date')->nullable();
        $table->string('srid')->nullable();
    }
}
