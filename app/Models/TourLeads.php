<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TourLeads extends Model
{
    use HasFactory;
    protected $table = 'tour_leads';
    protected $fillable =['id', 'tour_id', 'package_id', 'user_id', 'amount', 'qty','status', 'amount_status', 'created_at', 'updated_at'];

    public function Tour(){
        return $this->hasOne(TourVisits::class,'id','tour_id');
    }
    public function userData(){
        return $this->hasOne(User::class,'id','user_id');
    }
    public function followby()
    {
        return $this->hasOne(TourFollowup::class, 'lead_id','id');
    }
}