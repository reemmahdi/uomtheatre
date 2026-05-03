@extends('layouts.app')

@section('content')
    @livewire('dashboard.event-cancellation-notices', ['id' => $eventId])
@endsection
