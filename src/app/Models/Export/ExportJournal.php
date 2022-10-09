<?php

namespace App\Models\Export;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportJournal extends Model
{
    use HasFactory, UsesUuid;

    protected $table = 'export_journals';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'start_task', 'stop_task', 'task_flag', 'description_flag', 'name_task', 'project_id'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [
        'id'
    ];
}
