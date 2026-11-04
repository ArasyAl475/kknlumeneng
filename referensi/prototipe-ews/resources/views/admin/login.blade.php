@extends('layouts.app')
@section('title', 'Masuk admin')

@section('content')
    <div class="mx-auto max-w-sm space-y-4">
        <h1 class="text-3xl font-bold">Masuk admin</h1>
        <form method="POST" action="{{ route('login.proses') }}" class="blok space-y-4">
            @csrf
            @if ($errors->any())
                <div role="alert" class="rounded-lg border border-red-300 bg-red-50 p-3 text-red-900">{{ $errors->first() }}</div>
            @endif
            <div>
                <label for="email" class="mb-1 block font-bold">Email</label>
                <input id="email" name="email" type="email" required autofocus value="{{ old('email') }}" class="w-full rounded-lg border border-slate-300 p-3">
            </div>
            <div>
                <label for="password" class="mb-1 block font-bold">Password</label>
                <input id="password" name="password" type="password" required class="w-full rounded-lg border border-slate-300 p-3">
            </div>
            <button type="submit" class="w-full rounded-lg bg-[#14232B] p-3 font-bold text-white hover:bg-slate-700">Masuk</button>
        </form>
    </div>
@endsection
