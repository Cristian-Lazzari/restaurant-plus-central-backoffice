@extends('layouts.app')

@section('content')

    {{-- Section: Page header --}}
    <div class="page-header">
        <nav class="breadcrumb" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
            <span class="breadcrumb-sep" aria-hidden="true">›</span>
            <a href="{{ route('sites.show', $site) }}">{{ $site->name }}</a>
            <span class="breadcrumb-sep" aria-hidden="true">›</span>
            <span>{{ __('Modifica') }}</span>
        </nav>
        <h1 class="page-title">{{ __('Modifica sito') }}</h1>
        <div class="page-subtitle">{{ $site->name }}</div>
    </div>

    {{-- Section: Form --}}
    <div class="panel" style="max-width: 720px;">
        <h2 class="section-title" style="margin-bottom: 18px;">{{ __('Dati sito') }}</h2>
        <form method="POST" action="{{ route('sites.update', $site) }}">
            @method('PUT')
            @include('sites._form')
        </form>
    </div>

@endsection
