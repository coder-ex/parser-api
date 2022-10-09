<?php

namespace App\Services\MariaBA\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class ExciseReportEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->unsignedBigInteger('operation_id')->nullable(); // внутренний код операции
        $table->float('finishedPrice');                         // цена товара с учетом НДС
        $table->integer('operationTypeId');                     //  тип операции (тип операции 1 - продажа, 2 - возврат)
        $table->dateTime('fiscalDt');                           // время фискализации
        $table->unsignedInteger('docNumber');                   // номер фискального документа
        $table->string('fnNumber', 30);                         // номер фискального накопителя
        $table->string('regNumber', 30);                        // регистрационный номер ККТ
        $table->string('excise', 30);                           // акциз (он же киз)
        $table->dateTime('date', 3)->index();                            // дата, когда данные появились в системе (по ним и отслеживать изменение, появление новых)
    }
}
