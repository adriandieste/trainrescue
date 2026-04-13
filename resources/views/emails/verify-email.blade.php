@extends('layouts.email')

@section('content')
<div style="text-align: center; margin-bottom: 20px;">
    <img src="{{ asset('imagenes/trainrescue-logo-horizontal.png') }}" alt="Logo de Train & Rescue" style="width: 240px; height: auto;">
</div>

<h1 style="text-align: center; color: #333; margin: 20px 0;">Verifica tu cuenta en Train & Rescue</h1>

<p style="font-size: 16px; color: #555; line-height: 1.6;">
    ¡Hola {{ $user->name }}!
</p>

<p style="font-size: 16px; color: #555; line-height: 1.6;">
    Gracias por registrarte en Train & Rescue. Para completar tu registro y acceder a todas las funcionalidades, necesitamos que verifiques tu dirección de correo electrónico.
</p>

<div style="text-align: center; margin: 30px 0;">
    <a href="{{ $actionUrl }}" style="background-color: #3B82F6; color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: bold; display: inline-block;">
        Verificar Email
    </a>
</div>

<p style="font-size: 14px; color: #666; line-height: 1.6;">
    Si no reconoces esta cuenta, puedes ignorar este email. Si tienes problemas para verificar tu cuenta, copia y pega el siguiente enlace en tu navegador:
</p>

<p style="font-size: 12px; color: #999; word-break: break-all;">
    {{ $actionUrl }}
</p>

<p style="font-size: 14px; color: #666; line-height: 1.6; margin-top: 20px;">
    Este enlace expirará en 24 horas.
</p>

<hr style="border: none; border-top: 1px solid #e0e0e0; margin: 30px 0;">

<p style="font-size: 12px; color: #999; text-align: center;">
    Train & Rescue - Salvamento y Socorrismo
</p>
@endsection

