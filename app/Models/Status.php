<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    protected $fillable = ['name', 'display_name', 'description'];

    const DRAFT        = 'draft';
    const ADDED        = 'added';
    const UNDER_REVIEW = 'under_review';
    const ACTIVE       = 'active';
    const PUBLISHED    = 'published';
    const CLOSED       = 'closed';
    const CANCELLED    = 'cancelled';
    const END          = 'end';

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
