@extends('errors.layout')

@section('code', '403')
@section('title', 'Acesso negado')
@section('message', 'Você não tem permissão para acessar esta página. Se acha que isso é um engano, fale com o administrador.')

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" /><path d="m9.5 9.5 5 5" /><path d="m14.5 9.5-5 5" /></svg>
@endsection
