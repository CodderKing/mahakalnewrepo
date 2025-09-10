<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalAmountHistory extends Model
{
    use HasFactory;

    protected $table = 'withdrawal_amount_history';
    protected $fillable = ['id', 'type', 'vendor_id', 'ex_id', '', 'req_amount', 'approval_amount', 'message', 'status', 'transcation_id', 'payment_method', 'upi_code', 'bank_name', 'branch_code', 'ifsc_code', 'account_number', 'holder_name'];

    public function TourVisit()
    {
        return $this->hasOne(TourOrder::class, 'id', 'ex_id')->with(['Tour']);
    }

    public function Tour()
    {
        return $this->hasOne(TourAndTravel::class, 'id', 'vendor_id');
    }
    public function Event()
    {
        return $this->hasOne(Events::class, 'id', 'vendor_id');
    }

    public function Trust()
    {
        return $this->hasOne(DonateTrust::class, 'id', 'vendor_id');
    }

    public function TrustAds()
    {
        return $this->hasOne(DonateAds::class, 'id', 'ex_id');
    }
    public function EventOrg()
    {
        return $this->hasOne(EventOrganizer::class, 'id', 'vendor_id');
    }
}
