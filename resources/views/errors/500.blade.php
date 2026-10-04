@extends('errors.layout')

@section('code', '500')
@section('title', 'Erro interno')
@section('message', 'Algo deu errado do nosso lado. Já estamos cientes; tente novamente em alguns instantes.')

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2" /></svg>
@endsection

@section('actions')
    <button type="button" class="btn btn-primario" onclick="location.reload()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7L21 8" /><path d="M21 3v5h-5" /></svg>
        Tentar novamente
    </button>
    <a href="{{ url('/') }}" class="btn btn-secundario">Ir para o início</a>
@endsection
