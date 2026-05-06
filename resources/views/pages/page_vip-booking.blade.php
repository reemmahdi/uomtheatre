@extends('layouts.app')

@section('content')
    @livewire('dashboard.vip-booking', ['eventUuid' => $eventUuid])
@endsection
