<?php

namespace App\Models;

use App\Models\Astrologer\Astrologer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfflinePoojaOrder extends Model
{
    use HasFactory;

    public function leads()
    {
        return $this->hasOne(OfflineLead::class, 'id', 'leads_id');
    }

    public function offlinePooja()
    {
        return $this->hasOne(PoojaOffline::class, 'id', 'service_id');
    }

    public function customers()
    {
        return $this->hasOne(User::class, 'id', 'customer_id');
    }

    public function customer()
    {
        return $this->hasOne(User::class, 'id', 'customer_id');
    }

    public function package()
    {
        return $this->hasOne(Package::class, 'id', 'package_id');
    }

    public function payments()
    {
        return $this->hasOne(PaymentRequest::class, 'transaction_id', 'payment_id');
    }

    public function pandit()
    {
        return $this->hasOne(Astrologer::class, 'id', 'pandit_assign');
    }
}
