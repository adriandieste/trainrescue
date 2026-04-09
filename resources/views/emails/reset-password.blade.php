<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contraseña</title>
</head>
<body style="margin:0; padding:0; background:#f3f4f6; font-family:Arial, Helvetica, sans-serif; color:#111827;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6; padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px; background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 8px 24px rgba(15, 23, 42, 0.08);">
                <tr>
                    <td style="background:linear-gradient(180deg, #60a5fa 0%, #2563eb 100%); padding:24px; text-align:center;">
                        <img src="{{ asset('../../../imagenes/logoTrain&Rescue.png') }}" alt="Train & Rescue" style="width:72px; height:72px; object-fit:contain; border-radius:9999px; background:#ffffff; padding:6px; box-sizing:border-box;" />
                        <h1 style="margin:12px 0 4px; color:#ffffff; font-size:26px; line-height:1.2;">Train &amp; Rescue</h1>
                        <p style="margin:0; color:#dbeafe; font-size:14px;">Recuperación de acceso</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px 24px;">
                        <h2 style="margin:0 0 12px; font-size:20px; color:#111827;">Restablece tu contraseña</h2>
                        <p style="margin:0 0 12px; font-size:15px; color:#374151; line-height:1.6;">
                            Hemos recibido una solicitud para cambiar la contraseña de tu cuenta.
                        </p>
                        <p style="margin:0 0 24px; font-size:15px; color:#374151; line-height:1.6;">
                            Pulsa el siguiente boton para crear una nueva contraseña. Este enlace caduca en {{ $expireMinutes }} minutos.
                        </p>

                        <div style="text-align:center; margin-bottom:20px;">
                            <a href="{{ $actionUrl }}" style="display:inline-block; background:#ef4444; color:#ffffff; text-decoration:none; font-weight:700; padding:12px 20px; border-radius:12px; font-size:15px;">
                                Restablecer contraseña
                            </a>
                        </div>

                        <p style="margin:0 0 8px; font-size:13px; color:#6b7280; line-height:1.6;">
                            Si no solicitaste este cambio, puedes ignorar este correo.
                        </p>
                        <p style="margin:0; font-size:13px; color:#6b7280; line-height:1.6; word-break:break-all;">
                            Si el boton no funciona, copia y pega este enlace en tu navegador:<br>
                            <a href="{{ $actionUrl }}" style="color:#2563eb;">{{ $actionUrl }}</a>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>

