<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Builder;

class DonateTrust extends Model
{
    use HasFactory;
    protected $table = 'donate_trust';
    protected $fillable = ['id', 'trust_id', 'category_id', 'trust_email', 'name', 'trust_pan_card', 'pan_card', 'website', 'full_address', 'trust_name', 'description', 'memberlist',  'beneficiary_name', 'account_type', 'bank_name', 'ifsc_code', 'account_no', 'theme_image', 'gallery_image', 'twelve_a_certificate', 'eighty_g_certificate', 'niti_aayog_certificate', 'csr_certificate', 'e_anudhan_certificate', 'frc_certificate', 'verified_access_certificate', 'donate_commission', 'ad_commission', 'trust_total_amount', 'trust_total_withdrawal', 'trust_req_withdrawal_amount', 'admin_commission', 'status', 'is_approve', 'created_at', 'updated_at', 'astin_g_number', 'twelve_a_number', 'eighty_g_number', 'niti_aayog_number', 'csr_number', 'e_anudhan_number', 'frc_number', 'slug', 'trust_pan_card_image', 'pan_card_image', 'astin_g_certificate'];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('translate', function (Builder $builder) {
            $builder->with(['translations' => function ($query) {
                if (strpos(url()->current(), '/api')) {
                    return $query->where('locale', App::getLocale());
                } else {
                    return $query->where('locale', getDefaultLanguage());
                }
            }]);
        });

        static::creating(function ($model) {
            $model->trust_id = self::generateUniqueId();
        });
    }
    private static function generateUniqueId()
    {
        do {
            $uniqueId = 'DT' . rand(111, 999);
            $exists = self::where('trust_id', $uniqueId)->exists();
        } while ($exists);

        return $uniqueId;
    }

    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translationable');
    }

    public function category()
    {
        return $this->hasOne(DonateCategory::class, 'id', 'category_id');
    }

    public function getNameAttribute($name): string|null
    {
        if (strpos(url()->current(), '/admin') || strpos(url()->current(), '/vendor') || strpos(url()->current(), '/seller')) {
            return $name;
        }
        return $this->translations[0]->value ?? $name;
    }

    public function getDescriptionAttribute($name): string|null
    {
        if (strpos(url()->current(), '/admin') || strpos(url()->current(), '/vendor') || strpos(url()->current(), '/seller')) {
            return $name;
        }
        return $this->translations[1]->value ?? $name;
    }

    public function getTrustNameAttribute($name): string|null
    {
        if (strpos(url()->current(), '/admin') || strpos(url()->current(), '/vendor') || strpos(url()->current(), '/seller')) {
            return $name;
        }
        return $this->translations[2]->value ?? $name;
    }

    public function getFullAddressAttribute($name): string|null
    {
        if (strpos(url()->current(), '/admin') || strpos(url()->current(), '/vendor') || strpos(url()->current(), '/seller')) {
            return $name;
        }
        return $this->translations[3]->value ?? $name;
    }
}
