<?php

namespace App\Models\Export;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportTask extends Model
{
    use HasFactory, UsesUuid;

    protected $table = 'export_tasks';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'task', 'dateFrom', 'start_time', 'extended_fields', 'url', /*'class',*/ 'table', 'service_id'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [
        'id'
    ];

    public function service()
    {
        return $this->belongsTo(ExportService::class, 'service_id');
    }
}
