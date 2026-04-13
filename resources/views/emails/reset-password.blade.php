@extends('layouts.email')

@section('content')
<div style="text-align: center; margin-bottom: 20px;">
    <img src="{{ asset('imagenes/trainrescue-logo-horizontal.png') }}" alt="Logo de Train & Rescue" style="width: 240px; height: auto;">
</div>

<h1 style="text-align: center; color: #333; margin: 20px 0;">Restablece tu contraseña</h1>

<p style="font-size: 16px; color: #555; line-height: 1.6;">
    Hemos recibido una solicitud para cambiar la contraseña de tu cuenta.
</p>

<p style="font-size: 16px; color: #555; line-height: 1.6;">
    Pulsa el botón de abajo para crear una nueva contraseña. Este enlace caduca en {{ $expireMinutes }} minutos.
</p>

<div style="text-align: center; margin: 30px 0;">
    <a href="{{ $actionUrl }}" style="background-color: #ef4444; color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: bold; display: inline-block;">
        Restablecer contraseña
    </a>
</div>

<p style="font-size: 14px; color: #666; line-height: 1.6;">
    Si no solicitaste este cambio, puedes ignorar este email.
</p>

<p style="font-size: 14px; color: #666; line-height: 1.6;">
    Si el botón no funciona, copia y pega este enlace en tu navegador:
</p>

<p style="font-size: 12px; color: #999; word-break: break-all;">
    {{ $actionUrl }}
</p>

<hr style="border: none; border-top: 1px solid #e0e0e0; margin: 30px 0;">

<p style="font-size: 12px; color: #999; text-align: center;">
    Train & Rescue - Salvamento y Socorrismo
</p>
@endsection

