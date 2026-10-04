@extends('errors.layout')

@section('code', '419')
@section('title', 'Sessão expirada')
@section('message', 'Sua sessão expirou por inatividade. Recarregue a página e tente novamente.')

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><path d="M12 6v6l4 2" /></svg>
@endsection

@section('actions')
    <button type="button" class="btn btn-primario" onclick="location.reload()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7L21 8" /><path d="M21 3v5h-5" /></svg>
        Recarregar página
    </button>
    <a href="{{ url('/') }}" class="btn btn-secundario">Ir para o início</a>
@endsection
