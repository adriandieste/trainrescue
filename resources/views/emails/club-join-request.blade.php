@extends('layouts.email')
@section('content')
<div style="text-align: center; margin-bottom: 20px;">
    <img src="{{ asset('imagenes/trainrescue-logo-horizontal.png') }}" alt="Logo de Train & Rescue" style="width: 240px; height: auto;">
</div>
<h1 style="text-align: center; color: #333; margin: 20px 0;">Nueva solicitud para {{ $clubName }}</h1>
<p style="font-size: 16px; color: #555; line-height: 1.6;">
    Hola {{ $adminName }}!
</p>
<p style="font-size: 16px; color: #555; line-height: 1.6;">
    {{ $requesterName }} ha solicitado unirse a tu club <strong>{{ $clubName }}</strong>.
</p>
@if($requestMessage)
<div style="background-color: #f9f5e7; border-left: 4px solid #f59e0b; padding: 12px 16px; margin: 20px 0; border-radius: 4px;">
    <p style="font-size: 14px; color: #78350f; margin: 0; line-height: 1.6;">
        <strong>Mensaje del solicitante:</strong><br>
        "{{ $requestMessage }}"
    </p>
</div>
@endif
<p style="font-size: 14px; color: #666; line-height: 1.6;">
    <strong>Datos del solicitante:</strong><br>
    Nombre: {{ $requesterName }}<br>
    Email: {{ $requesterEmail }}
</p>
<p style="font-size: 14px; color: #666; line-height: 1.6; margin-top: 20px;">
    Para aceptar o rechazar esta solicitud, utiliza los siguientes botones:
</p>
<div style="text-align: center; margin: 30px 0;">
    <a href="{{ $acceptUrl }}" style="background-color: #16a34a; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold; display: inline-block; margin-right: 8px;">
        ✓ Aceptar solicitud
    </a>
    <a href="{{ $rejectUrl }}" style="background-color: #dc2626; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold; display: inline-block;">
        ✕ Rechazar solicitud
    </a>
</div>
<p style="font-size: 12px; color: #999; word-break: break-all;">
    <strong>Aceptar:</strong> {{ $acceptUrl }}<br>
    <strong>Rechazar:</strong> {{ $rejectUrl }}
</p>
<p style="font-size: 14px; color: #666; line-height: 1.6; margin-top: 20px;">
    Estos enlaces caducan en 72 horas.
</p>
<hr style="border: none; border-top: 1px solid #e0e0e0; margin: 30px 0;">
<p style="font-size: 12px; color: #999; text-align: center;">
    Train & Rescue - Salvamento y Socorrismo
</p>
@endsection
