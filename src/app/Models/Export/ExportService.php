<?php

namespace App\Models\Export;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportService extends Model
{
    use HasFactory, UsesUuid;

    protected $table = 'export_services';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'project_id', 'name', 'secret'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [
        'id'
    ];

    public function tasks()
    {
        return $this->hasMany(ExportTask::class, 'service_id');
    }
}
