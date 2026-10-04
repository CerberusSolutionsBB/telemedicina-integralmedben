@extends('errors.layout')

@section('code', '405')
@section('title', 'Ação não permitida')
@section('message', 'Esta ação não pode ser feita por este caminho. Volte e tente novamente pela tela do sistema.')

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><path d="m4.9 4.9 14.2 14.2" /></svg>
@endsection
