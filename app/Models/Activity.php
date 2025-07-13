<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

$logs = Activity::latest()->get();
class Activity extends Model
{
    protected $table = 'activity_log';

    protected $guarded = [];
}
