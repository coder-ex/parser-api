<?php

namespace App\Services\Ozon\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class FboListEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->bigInteger('project_id')->index();
        $table->string('city', 255)->nullable();
        $table->string('delivery_type', 1000)->nullable();
        $table->boolean('is_premium')->nullable();
        $table->string('payment_type_group_name', 1000)->nullable();
        $table->string('region', 1000)->nullable();
        $table->bigInteger('warehouse_id')->nullable();
        $table->string('warehouse_name', 1000)->nullable();
        $table->bigInteger('cancel_reason_id')->nullable();
        $table->timestamp('created_at', 3)->nullable();
        $table->string('actions', 255)->nullable();
        $table->string('client_price', 255)->nullable();
        $table->float('commission_amount', 8, 2)->nullable();
        $table->integer('commission_percent')->nullable();
        $table->float('old_price', 8, 2)->nullable();
        $table->float('payout', 8, 2)->nullable();
        $table->float('amount', 8, 2)->nullable();
        $table->dateTime('moment')->nullable();
        $table->float('price', 8, 2)->nullable();
        $table->bigInteger('product_id')->nullable();
        $table->integer('quantity')->nullable();
        $table->float('total_discount_percent', 8, 2)->nullable();
        $table->float('total_discount_value', 8, 2)->nullable();
        $table->timestamp('in_process_at', 3)->nullable();
        $table->bigInteger('order_id')->nullable();
        $table->string('order_number', 255)->nullable();
        $table->string('posting_number', 255)->nullable();
        $table->string('status', 255)->nullable();
        $table->float('marketplace_service_item_fulfillment', 8, 2)->nullable();
        $table->float('marketplace_service_item_pickup', 8, 2)->nullable();
        $table->float('marketplace_service_item_dropoff_pvz', 8, 2)->nullable();
        $table->float('marketplace_service_item_dropoff_sc', 8, 2)->nullable();
        $table->float('marketplace_service_item_dropoff_ff', 8, 2)->nullable();
        $table->float('marketplace_service_item_direct_flow_trans', 8, 2)->nullable();
        $table->float('marketplace_service_item_return_flow_trans', 8, 2)->nullable();
        $table->float('marketplace_service_item_deliv_to_customer', 8, 2)->nullable();
        $table->float('marketplace_service_item_return_not_deliv_to_customer', 8, 2)->nullable();
        $table->float('marketplace_service_item_return_part_goods_customer', 8, 2)->nullable();
        $table->float('marketplace_service_item_return_after_deliv_to_customer', 8, 2)->nullable();
    }
}