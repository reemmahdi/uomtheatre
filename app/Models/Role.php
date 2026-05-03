<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'display_name', 'description'];


    const SUPER_ADMIN      = 'super_admin';
    const EVENT_MANAGER    = 'event_manager';
    const THEATER_MANAGER  = 'theater_manager';
    const RECEPTIONIST     = 'receptionist';
    const UNIVERSITY_OFFICE = 'university_office';
    const USER             = 'user';


    public function users()
    {
        return $this->hasMany(User::class);
    }
}