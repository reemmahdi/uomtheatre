<?php

namespace App\Livewire\Dashboard;

use App\Livewire\BaseComponent;
use App\Models\User;
use App\Models\Role;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\Status;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.app')]
#[Title('الرئيسية')]
class Index extends BaseComponent
{
    public function render()
    {
        $roleName = Auth::user()->role->name;
        $userId = Auth::id();

        $publishedStatus = Status::where('name', 'published')->first();
        $data = [
            'roleName'          => $roleName,
            'totalEvents'       => Event::count(),
            'publishedEvents'   => $publishedStatus ? Event::where('status_id', $publishedStatus->id)->count() : 0,
            'totalReservations' => Reservation::where('status', '!=', 'cancelled')->count(),
            'totalCheckedIn'    => Reservation::where('status', 'checked_in')->count(),
        ];

        if ($roleName === 'super_admin') {
            $roles = Role::withCount('users')->get();
            $roleColors = ['super_admin'=>'#e74c3c','event_manager'=>'#f39c12','theater_manager'=>'#2e75b6','receptionist'=>'#27ae60','university_office'=>'#8e44ad','user'=>'#95a5a6'];
            $data += [
                'totalUsers'    => User::count(),
                'activeUsers'   => User::where('is_active', true)->count(),
                'inactiveUsers' => User::where('is_active', false)->count(),
                'totalRoles'    => Role::count(),
                'rolesDistribution' => $roles->map(fn($r) => ['name'=>$r->name,'display_name'=>$r->display_name,'count'=>$r->users_count,'color'=>$roleColors[$r->name]??'#95a5a6']),
                'recentUsers'   => User::with('role')->orderBy('created_at','desc')->take(5)->get(),
            ];
        } elseif ($roleName === 'theater_manager') {
            $draftStatus     = Status::where('name', 'draft')->first();
            $addedStatusTm   = Status::where('name', 'added')->first();
            $cancelledStatus = Status::where('name', 'cancelled')->first();

            $data += [
                'totalEvents'        => Event::where('created_by', $userId)->count(),
                'sentToMediaEvents'  => $addedStatusTm ? Event::where('created_by', $userId)->where('status_id', $addedStatusTm->id)->count() : 0,
                'draftEvents'        => $draftStatus ? Event::where('created_by', $userId)->where('status_id', $draftStatus->id)->count() : 0,
                'cancelledEvents'    => $cancelledStatus ? Event::where('created_by', $userId)->where('status_id', $cancelledStatus->id)->count() : 0,
            ];
        } elseif ($roleName === 'event_manager') {
            $addedStatus  = Status::where('name','added')->first();
            $activeStatus = Status::where('name','active')->first();
            $data += [
                'pendingReview' => $addedStatus ? Event::where('status_id',$addedStatus->id)->count() : 0,
                'activeEvents'  => $activeStatus ? Event::where('status_id',$activeStatus->id)->count() : 0,
            ];
        } elseif ($roleName === 'receptionist') {
            $data['checkedInToday'] = Reservation::where('status','checked_in')->whereDate('checked_in_at',today())->count();
        }

        return view('livewire.dashboard.index', $data);
    }
}
