@extends('layouts.email')

@section('content')
<div style="text-align: center; margin-bottom: 20px;">
    <img src="{{ asset('imagenes/trainrescue-logo-horizontal.png') }}" alt="Logo de Train & Rescue" style="width: 240px; height: auto;">
</div>

<h1 style="text-align: center; color: #333; margin: 20px 0;">¡Bienvenido/a a {{ $clubName }}! 🎉</h1>

<p style="font-size: 16px; color: #555; line-height: 1.6;">
    ¡Hola {{ $userName }}!
</p>

<p style="font-size: 16px; color: #555; line-height: 1.6;">
    ¡Nos complace comunicarte que tu solicitud para unirte a <strong>{{ $clubName }}</strong> ha sido <strong style="color: #2562e9;">aceptada</strong>! Ya eres parte del equipo.
</p>

<div style="background-color: #f0fdf4; border-left: 4px solid #2562e9; padding: 12px 16px; margin: 20px 0; border-radius: 4px;">
    <p style="font-size: 14px; margin: 0; line-height: 1.6;">
        <strong>Detalles de tu club:</strong><br>
        Club: <strong>{{ $clubName }}</strong><br>
        Entrenador: <strong>{{ $adminName }}</strong>
        @if($clubDescription)
        <br>Descripción: {{ $clubDescription }}
        @endif
    </p>
</div>

<p style="font-size: 14px; color: #666; line-height: 1.6; margin-top: 20px;">
    Accede a la plataforma para consultar tus entrenamientos, seguir tu progreso y conectar con tu entrenador.
</p>

<div style="text-align: center; margin: 30px 0;">
    <a href="{{ $dashboardUrl }}" style="background-color: #2563eb; color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: bold; display: inline-block;">
        Ir a mi Dashboard
    </a>
</div>

<p style="font-size: 12px; color: #999; word-break: break-all;">
    {{ $dashboardUrl }}
</p>

<p style="font-size: 14px; color: #666; line-height: 1.6; margin-top: 20px;">
    Si el botón no funciona, copia y pega el enlace anterior en tu navegador.
</p>

<hr style="border: none; border-top: 1px solid #e0e0e0; margin: 30px 0;">

<p style="font-size: 12px; color: #999; text-align: center;">
    Train & Rescue - Salvamento y Socorrismo
</p>
@endsection
