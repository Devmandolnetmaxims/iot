<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceLink extends Model
{
    protected $table = 'DeviceLink';
    protected $primaryKey = 'D_Link';
    public $timestamps = false; // since your table doesn't have created_at/updated_at

    protected $fillable = [
        'D_Link', 'P1', 'P2', 'CAR1', 'CAR2', 'CAR3', 'CAR4', 'CAR5', 'CAR6', 'CAR7', 'CAR8'
    ];
}
