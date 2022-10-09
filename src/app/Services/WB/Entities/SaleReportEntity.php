<?php

namespace App\Services\WB\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class SaleReportEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->string('project_id', 255);
        $table->integer('realizationreport_id')->nullable();
        $table->string('suppliercontract_code', 255)->nullable();
        $table->bigInteger('rrd_id')->nullable();
        $table->bigInteger('gi_id')->nullValue();
        $table->string('subject_name', 255)->nullable();
        $table->bigInteger('nm_id')->nullable();
        $table->string('brand_name', 100)->nullable();
        $table->string('sa_name', 100)->nullable();
        $table->string('ts_name', 15)->nullable();
        $table->string('barcode', 50)->nullable();
        $table->string('doc_type_name', 100)->nullable();
        $table->integer('quantity')->default(0);
        $table->double('retail_price', 8, 2)->default(0.00);
        $table->double('retail_amount', 8, 2)->default(0.00);
        $table->double('sale_percent', 8, 2)->default(0.00);
        $table->double('commission_percent', 8, 2)->default(0.00);
        $table->string('office_name', 100)->nullable();
        $table->string('supplier_oper_name', 100)->nullable();
        $table->dateTime('order_dt', 0);
        $table->dateTime('sale_dt', 0);
        $table->dateTime('rr_dt', 0);
        $table->bigInteger('shk_id')->nullable();
        $table->double('retail_price_withdisc_rub', 8, 2)->default(0.00);
        $table->integer('delivery_amount')->default(0);
        $table->integer('return_amount')->default(0);
        $table->double('delivery_rub', 8, 2)->default(0.00);
        $table->string('gi_box_type_name', 100)->nullable();
        $table->integer('product_discount_for_report')->default(0);
        $table->integer('supplier_promo')->default(0);
        $table->bigInteger('rid')->nullable();
        $table->double('ppvz_spp_prc', 8, 3)->default(0.000);
        $table->double('ppvz_kvw_prc_base', 8, 4)->default(0.0000);
        $table->double('ppvz_kvw_prc', 8, 4)->default(0.0000);
        $table->double('ppvz_sales_commission', 8, 2)->default(0.00);
        $table->double('ppvz_for_pay', 8, 2)->default(0.00);
        $table->double('ppvz_reward', 8, 2)->default(0.00);
        $table->double('ppvz_vw', 8, 2)->default(0.00);
        $table->double('ppvz_vw_nds', 8, 2)->default(0.00);
        $table->integer('ppvz_office_id')->nullable();
        $table->string('ppvz_office_name', 100)->nullable();
        $table->integer('ppvz_supplier_id')->default(0);
        $table->string('ppvz_supplier_name', 100)->nullable();
        $table->string('ppvz_inn', 50)->nullable();
        $table->string('declaration_number', 100)->nullable();
        $table->string('bonus_type_name', 1000)->nullable();
        $table->string('sticker_id', 100)->nullable();
        $table->string('site_country', 15)->nullable();
        $table->double('penalty', 8, 2)->default(0);
        $table->double('additional_payment', 8, 2)->default(0);
    }
}