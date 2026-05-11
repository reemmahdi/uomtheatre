@extends('layouts.app')

@php
    use App\Models\Event;
    $event = Event::where('uuid', $eventUuid)->firstOrFail();
@endphp

@section('content')
    @livewire('dashboard.seat-availability', ['eventUuid' => $eventUuid], key($eventUuid))
@endsection
