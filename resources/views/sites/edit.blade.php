@extends('layouts.app')

@section('content')
    <h1>Edit site</h1>
    <div class="panel">
        <form method="POST" action="{{ route('sites.update', $site) }}">
            @method('PUT')
            @include('sites._form')
        </form>
    </div>
@endsection
