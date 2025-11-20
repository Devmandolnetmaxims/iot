<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestMuguhwa extends Model
{
    //
    protected $table = 'TestMuguhwa';

    protected $fillable = [
        'DEVICE', 'TIME', 'BEGIN', 'LAST', 'EVENT',
        'ACTIVE', 'PIR', 'TOF', 'UV', 'MM', 'TEMP'
    ];
}
