<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToSchool;

class ParentProfile extends Model
{
    use BelongsToSchool;
    protected $fillable = [
        'user_id','billing_type','monthly_fee','yearly_fee',
        'insurance_fee','transport_fee','canteen_fee','starts_month','notes'
    ];

    public function parent()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

