<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class VendorEmployees extends Authenticatable
{
    use HasApiTokens, Notifiable;
    protected $table = 'vendor_employee';
    protected $fillable = ['id', 'identify_number', 'type', 'name', 'phone', 'email', 'emp_role_id', 'image', 'password', 'status', 'relation_id', 'created_at', 'updated_at'];

    public function Role()
    {
        return $this->hasone(VendorRoles::class, 'id', 'emp_role_id');
    }

    public function seller()
    {
        return $this->hasone(Seller::class, 'id', 'relation_id');
    }
    public function Tour()
    {
        return $this->hasone(TourAndTravel::class, 'id', 'relation_id');
    }
    public function Trust()
    {
        return $this->hasone(DonateTrust::class, 'id', 'relation_id');
    }
    public function Event()
    {
        return $this->hasone(EventOrganizer::class, 'id', 'relation_id');
    }
}
