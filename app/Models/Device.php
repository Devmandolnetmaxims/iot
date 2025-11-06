<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'DeviceInfo2'; // your table name

    protected $primaryKey = 'DEVICE'; // use DEVICE as unique identifier (or whatever works best)
    public $incrementing = false;     // since DEVICE is not auto-increment
    protected $keyType = 'string';    // because DEVICE is varchar(4)

    public $timestamps = false;       // disable created_at, updated_at since not present

    protected $fillable = [
        'DEVICE',
        'CAR',
        'TYPE',
        'INSTALL',
        'CAR_LINK',
        'P1',
        'P2',
        'TrainArr',
        'TrainDep',
    ];
}
