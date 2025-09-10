<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PoojaForecast extends Model
{
    use HasFactory;

    protected $table = 'pooja_forecasts';

    protected $fillable = [
        'service_id',
        'booking_date',
        'type',
        'category',
        'total_orders',
        'total_users',
        'earnings',
        'week_days',
        'start_datetime'
    ];

    protected $dates = ['booking_date'];

    // Relations (if needed)
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
    //review
    public function review()
    {
        return $this->hasMany(ServiceReview::class, 'service_id', 'id');
    }

    public function PoojaOrderReview()
    {
        return $this->hasMany(Service_order::class, 'service_id', 'id');
    }
}
