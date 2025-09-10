<?php

namespace App\Models\Astrologer;

use App\Models\AstrologerCategory;
use App\Models\Category;
use App\Models\Chadhava_orders;
use App\Models\Service;
use App\Models\Service_order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use DB;

class Astrologer extends Authenticatable
{
    use HasFactory;
    use SoftDeletes;
    use Notifiable, HasApiTokens;
    protected $appends=['ordercount'];
    protected $chadhavaappends=['chadhavaordercount'];

    public function getOrdercountAttribute(){
        return  Service_order::where('pandit_assign',$this->attributes['id'])->where('booking_date','=', date('Y-m-d'))->groupBy('service_id')->select('service_id', DB::raw('count(*) as total'))->count();
    }
    public function getOrderChadhavacountAttribute(){
        return  Chadhava_orders::where('pandit_assign',$this->attributes['id'])->where('booking_date','=', date('Y-m-d'))->groupBy('service_id')->select('service_id', DB::raw('count(*) as total'))->count();
    }

    public function getOtherSkillsAttribute($value)
    {
        if($value!='null'&&!empty($value))
        return Skills::whereIn('id',json_decode($value))->get();
    }
    
    public function getIsPanditPoojaCategoryAttribute($value)
    {
        if($value!='null'&&!empty($value))
        return Category::whereIn('id',json_decode($value))->get();
    }
    
    public function getImageAttribute($value)
    {
        if($value!='null'&&!empty($value))
        return asset('storage/app/public/astrologers/' . $value);
    }   
    
    public function getPancardImageAttribute($value)
    {
        if($value!='null'&&!empty($value))
        return asset('storage/app/public/astrologers/pancard/' . $value);
    }   
    
    public function getAdharcardFrontImageAttribute($value)
    {
        if($value!='null'&&!empty($value))
        return asset('storage/app/public/astrologers/aadhar/' . $value);
    }   
    
    public function getAdharcardBackImageAttribute($value)
    {
        if($value!='null'&&!empty($value))
        return asset('storage/app/public/astrologers/aadhar/' . $value);
    }   
    
    public function getBankPassbookImageAttribute($value)
    {
        if($value!='null'&&!empty($value))
        return asset('storage/app/public/astrologers/bankpassbook/' . $value);
    }   

    public function getCategoryAttribute($value)
    {
        if($value!='null'&&!empty($value))
        return AstrologerCategory::whereIn('id',json_decode($value))->get();
    }
    
    public function getLanguageAttribute($value)
    {
        if($value!='null'&&!empty($value))
        return json_decode($value);
    }

    public function primarySkill(){
        return $this->hasOne(Skills::class,'id','primary_skills');
    }

    public function orders(){
        return $this->hasMany(Service_order::class,'pandit_assign','id');
    }
    public function availability(){
        return $this->hasOne(Availability::class,'astrologer_id','id');
    }
}