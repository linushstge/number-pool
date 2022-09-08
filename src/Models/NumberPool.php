<?php

namespace linushstge\NumberPool\Models;

use Illuminate\Database\Eloquent\Model;

class NumberPool extends Model
{
    protected $table = 'number_pool';

    protected $fillable = [
        'key',
        'description',
    ];
}
