@extends('layouts.app')

@section('content')
    @livewire('dashboard.event-cancellation-notices', ['eventUuid' => $eventUuid])
@endsection
