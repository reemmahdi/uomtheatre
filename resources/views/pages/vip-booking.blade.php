@extends('layouts.app', ['title' => 'حجز مقاعد الوفود'])
@section('content')
    <livewire:dashboard.vip-booking :id="$eventId" />
@endsection
