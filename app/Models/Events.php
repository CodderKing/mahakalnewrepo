<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Builder;

class Events extends Model
{
    use HasFactory;
    protected $table = 'events';

    protected $fillable = ['id', 'unique_id', 'event_name', 'slug', 'category_id', 'organizer_by', 'informational_status', 'event_organizer_id', 'event_about', 'event_schedule', 'event_attend', 'event_team_condition', 'age_group', 'event_artist', 'language', 'days', 'start_to_end_date', 'all_venue_data', 'package_list', 'event_image', 'images', 'youtube_video', 'meta_title', 'meta_description', 'meta_image', 'event_approve_amount', 'approve_amount_status', 'commission_live', 'commission_seats', 'booking_seats', 'org_withdrawable_balance', 'org_withdrawable_pending', 'org_withdrawable_ready', 'org_total_commission', 'org_total_tax', 'org_collected_cash', 'event_interested', 'created_at', 'updated_at', 'status', 'profit_information', 'is_approve'];


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
            $model->unique_id = self::generateUniqueId();
        });
    }
    private static function generateUniqueId()
    {
        $lastRecord = self::where('unique_id', 'like', 'E%')->orderBy('id', 'desc')->first();
        if ($lastRecord) {
            $lastNumber = (int) substr($lastRecord->unique_id, 2); // remove 'E'
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        return 'E' . str_pad($newNumber, 4, '0', STR_PAD_LEFT); // E0001, E0002, ...
    }

    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translationable');
    }

    public function categorys()
    {
        return $this->hasOne(EventCategory::class, 'id', 'category_id')->with('translations');
    }
    public function organizers()
    {
        return $this->hasOne(EventOrganizer::class, 'id', 'event_organizer_id')->with('translations');
    }
    public function eventArtist()
    {
        return $this->hasOne(Eventartist::class, 'id', 'event_artist');
    }

    public function EventOrder()
    {
        return $this->hasMany(EventOrder::class, 'event_id', 'id');
    }

    public function getEventNameAttribute($name): string|null
    {
        if (strpos(url()->current(), '/admin') || strpos(url()->current(), '/vendor') || strpos(url()->current(), '/seller')  || strpos(url()->current(), '/event-vendor')) {
            return $name;
        }
        return $this->translations[0]->value ?? $name;
    }
    public function getEventAboutAttribute($name): string|null
    {
        if (strpos(url()->current(), '/admin') || strpos(url()->current(), '/vendor') || strpos(url()->current(), '/seller')  || strpos(url()->current(), '/event-vendor')) {
            return $name;
        }
        return $this->translations[1]->value ?? $name;
    }
    public function getEventScheduleAttribute($name): string|null
    {
        if (strpos(url()->current(), '/admin') || strpos(url()->current(), '/vendor') || strpos(url()->current(), '/seller')  || strpos(url()->current(), '/event-vendor')) {
            return $name;
        }
        return $this->translations[2]->value ?? $name;
    }
    public function getEventAttendAttribute($name): string|null
    {
        if (strpos(url()->current(), '/admin') || strpos(url()->current(), '/vendor') || strpos(url()->current(), '/seller')  || strpos(url()->current(), '/event-vendor')) {
            return $name;
        }
        return $this->translations[3]->value ?? $name;
    }
    public function getEventTeamConditionAttribute($name): string|null
    {
        if (strpos(url()->current(), '/admin') || strpos(url()->current(), '/vendor') || strpos(url()->current(), '/seller')  || strpos(url()->current(), '/event-vendor')) {
            return $name;
        }
        return $this->translations[4]->value ?? $name;
    }

    public function review()
    {
        return $this->hasMany(EventsReview::class, 'event_id', 'id');
    }

    public function EventOrderReview()
    {
        return $this->hasMany(EventsReview::class, 'event_id', 'id');
    }
}