<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'group',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }

    const EVENTS_CREATE           = 'events.create';
    const EVENTS_EDIT             = 'events.edit';
    const EVENTS_DELETE           = 'events.delete';
    const EVENTS_SEND_FOR_APPROVAL = 'events.send_for_approval';
    const EVENTS_CANCEL           = 'events.cancel';
    const EVENTS_APPROVE_THEATER  = 'events.approve_theater';
    const EVENTS_APPROVE_OFFICE   = 'events.approve_office';
    const EVENTS_PUBLISH          = 'events.publish';
    const EVENTS_NOTIFY_ALL       = 'events.notify_all';

    const VIP_MANAGE              = 'vip.manage';
    const VIP_ASSIGN_SEATS        = 'vip.assign_seats';

    const CHECKIN_SCAN            = 'checkin.scan';

    const USERS_MANAGE            = 'users.manage';
    const STATS_VIEW              = 'stats.view';
    const PERMISSIONS_MANAGE      = 'permissions.manage';
}
