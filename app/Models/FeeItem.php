<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToSchool;

class FeeItem extends Model
{
    use BelongsToSchool;
    protected $fillable = ['name','billing_type','default_amount','due_month','is_active'];

}
