<?php

namespace App\Services\MariaBA\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class SaleReportEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->BigInteger('realizationreport_id');         // Номер отчета
        $table->string('suppliercontract_code')->nullable();// Договор
        $table->BigInteger('rrd_id');                       // Номер строки
        $table->BigInteger('gi_id');                        // Номер поставки
        $table->string('subject_name')->index()->nullable();// Предмет
        $table->BigInteger('nm_id')->nullable();            // Артикул
        $table->string('brand_name')->index()->nullable();  // Бренд
        $table->string('sa_name')->index()->nullable();     // Артикул поставщика
        $table->string('ts_name')->nullable();              // Размер
        $table->string('barcode')->nullable();              // Баркод
        $table->string('doc_type_name');                    // Тип документа
        $table->BigInteger('quantity');                     // Количество
        $table->BigInteger('retail_price');                 // Цена розничная
        $table->float('retail_amount');                     // Сумма продаж(Возвратов)
        $table->float('sale_percent');                      // Согласованная скидка
        $table->float('commission_percent');                // Процент комиссии
        $table->string('office_name')->nullable();          // Склад
        $table->string('supplier_oper_name');               // Обоснование для оплаты
        $table->date('order_dt');                           // Даты заказа
        $table->date('sale_dt');                            // Дата продажи
        $table->date('rr_dt')->index();                     // Дата операции
        $table->BigInteger('shk_id');                       // ШК
        $table->float('retail_price_withdisc_rub');         // Цена розничная с учетом согласованной скидки
        $table->BigInteger('delivery_amount');              // Кол-во доставок
        $table->BigInteger('return_amount');                // Кол-во возвратов
        $table->float('delivery_rub');                      // Стоимость логистики
        $table->string('gi_box_type_name');                 // Тип коробов
        $table->BigInteger('product_discount_for_report');  // Согласованный продуктовый дисконт
        $table->BigInteger('supplier_promo');               // Промокод
        $table->BigInteger('rid');                          // Уникальный идентификатор позиции заказа
        $table->float('ppvz_spp_prc');                      // Скидка постоянного Покупателя (СПП)
        $table->float('ppvz_kvw_prc_base');                 // Размер кВВ без НДС, % Базовый
        $table->float('ppvz_kvw_prc');                      // Итоговый кВВ без НДС, %
        $table->float('ppvz_sales_commission');             // Вознаграждение с продаж до вычета услуг поверенного, без НДС
        $table->float('ppvz_for_pay');                      // К перечислению Продавцу за реализованный Товар
        $table->float('ppvz_reward');                       // Возмещение Расходов услуг поверенного
        $table->float('ppvz_vw');                           // Вознаграждение Вайлдберриз (ВВ), без НДС
        $table->float('ppvz_vw_nds');                       // НДС с Вознаграждения Вайлдберриз
        $table->BigInteger('ppvz_office_id');               // Номер офиса
        $table->string('ppvz_office_name')->nullable();     // Наименование офиса доставки
        $table->BigInteger('ppvz_supplier_id');             // Номер партнера
        $table->string('declaration_number');               // Номер таможенной декларации
        $table->string('sticker_id')->nullable();           // Аналогично стикеру, который клеится на товар в процессе сборки
        $table->string('ppvz_supplier_name')->nullable();   // Партнер
        $table->string('ppvz_inn')->nullable();             // ИНН партнера
        $table->string('bonus_type_name')->nullable();      // из старой выгрузки
        $table->date('dag_date')->nullable();
        $table->string('site_country')->nullable();         // Страна продажи
        $table->float('penalty')->nullable();               // 
        $table->float('additional_payment')->nullable();    // 
        $table->string('srid')->nullable();
        $table->date('date_from')->nullable();
        $table->date('date_to')->nullable();
    }
}
