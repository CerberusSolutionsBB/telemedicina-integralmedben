@extends('errors.layout')

@section('code', '404')
@section('title', 'Página não encontrada')
@section('message', 'O endereço que você acessou não existe ou foi removido. Confira o link e tente novamente.')

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8" /><path d="m21 21-4.3-4.3" /><path d="M8.5 11h5" /></svg>
@endsection
