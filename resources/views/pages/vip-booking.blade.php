@extends('layouts.app', ['title' => 'إدارة حجز المقاعد'])
@section('content')
    <livewire:dashboard.vip-booking :id="$eventId" />
@endsection
