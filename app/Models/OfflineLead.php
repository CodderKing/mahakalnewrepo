<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfflineLead extends Model
{
    use HasFactory;

    protected $fillable = [
        'pooja_id',
        'lead_no',
        'order_id',
        'package_id',
        'package_name',
        'noperson',
        'package_main_price',
        'package_price',
        'person_name',
        'person_phone',
        'payment_status',
        'status'
    ];

    public function offlinePooja()
    {
        return $this->hasOne(PoojaOffline::class, 'id', 'pooja_id');
    }

    public function followBy()
    {
        return $this->hasOne(OfflinepoojaFollowup::class, 'lead_id', 'id')->latest('last_date');
    }
}