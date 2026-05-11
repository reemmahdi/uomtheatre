@extends('layouts.app')

@section('content')
    @livewire('dashboard.vip-guests', ['eventUuid' => $eventUuid])
@endsection
