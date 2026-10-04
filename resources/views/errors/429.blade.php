@extends('errors.layout')

@section('code', '429')
@section('title', 'Muitas tentativas')
@section('message', 'Você fez muitas requisições em pouco tempo. Aguarde um instante e tente novamente.')

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 22h14" /><path d="M5 2h14" /><path d="M17 22v-4.2a2 2 0 0 0-.6-1.4L12 12l-4.4 4.4a2 2 0 0 0-.6 1.4V22" /><path d="M7 2v4.2a2 2 0 0 0 .6 1.4L12 12l4.4-4.4a2 2 0 0 0 .6-1.4V2" /></svg>
@endsection

@section('actions')
    <button type="button" class="btn btn-primario" onclick="location.reload()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7L21 8" /><path d="M21 3v5h-5" /></svg>
        Tentar novamente
    </button>
    <a href="{{ url('/') }}" class="btn btn-secundario">Ir para o início</a>
@endsection
