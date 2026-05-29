@extends('layouts.app')

@section('content')
    <h1>New site</h1>
    <div class="panel">
        <form method="POST" action="{{ route('sites.store') }}">
            @include('sites._form')
        </form>
    </div>
@endsection
