@extends('errors.layout')

@section('code', '502')
@section('title', 'Serviço indisponível')
@section('message', 'Um serviço do sistema não respondeu como esperado. Tente novamente em alguns instantes.')

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v10" /><path d="M18.4 6.6a9 9 0 1 1-12.8 0" /></svg>
@endsection

@section('actions')
    <button type="button" class="btn btn-primario" onclick="location.reload()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7L21 8" /><path d="M21 3v5h-5" /></svg>
        Tentar novamente
    </button>
    <a href="{{ url('/') }}" class="btn btn-secundario">Ir para o início</a>
@endsection
