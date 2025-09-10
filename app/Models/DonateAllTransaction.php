<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DonateAllTransaction extends Model
{
    use HasFactory;
    protected $table = "donate_all_transaction";

    protected $fillable = ['id', 'trans_id', 'type', 'user_id', 'user_name', 'user_phone', 'pan_card', 'trust_id', 'ads_id', 'amount', 'transaction_id', 'amount_status', 'admin_commission', 'final_amount', 'payment_requests_id', 'transction_link', 'information', 'created_at', 'updated_at'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->trans_id = self::generateUniqueId();
        });
    }
    private static function generateUniqueId()
    {
        $lastRecord = self::where('trans_id', 'like', 'DT%')
            ->orderBy('id', 'desc')
            ->first();
        if ($lastRecord) {
            $lastNumber = (int) substr($lastRecord->trans_id, 2); // remove 'DT'
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return 'DT' . str_pad($newNumber, 4, '0', STR_PAD_LEFT); // DT0001, DT0002, ...
    }

    public function users()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
    public function adsTrust()
    {
        return $this->hasOne(DonateAds::class, 'id', 'ads_id')->with('Purpose');
    }
    public function getTrust()
    {
        // return $this->hasOne(DonateTrust::class,'id','trust_id');
        return $this->hasOne(DonateTrust::class, 'id', 'trust_id');
    }
}