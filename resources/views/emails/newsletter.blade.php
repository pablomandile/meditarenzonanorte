{{--
    El newsletter, escrito como se escriben los mails y no como se escribe una página:
    tablas anidadas, ancho fijo de 600 y los estilos en cada etiqueta. Outlook no
    entiende flex ni grid, y Gmail borra el <style> del <head>, así que acá no hay
    clases ni hojas de estilo: lo que no esté en el atributo style no existe.

    Los dos *|…|* del pie son merge tags de Mailchimp y no se tocan: sin un enlace de
    baja clickeable Mailchimp rechaza la campaña, y la dirección postal es obligatoria
    por ley antispam. El enlace de baja tiene que ser el href de un <a>, no texto suelto.
--}}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $newsletter->subject }}</title>
</head>
<body style="margin:0; padding:0; width:100% !important; background-color:#FFF1E6; font-family:Helvetica,Arial,sans-serif; -webkit-text-size-adjust:100%;">

{{-- El renglón que se lee en la bandeja de entrada debajo del asunto, y que no se ve al abrir. --}}
@if (filled($newsletter->preview_text))
    <div style="display:none; max-height:0; overflow:hidden; mso-hide:all; font-size:1px; line-height:1px; color:#FFF1E6;">
        {{ $newsletter->preview_text }}
    </div>
@endif

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#FFF1E6;">
    <tr>
        <td align="center" style="padding:24px 12px;">

            <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:100%; background-color:#ffffff; border-radius:12px; overflow:hidden;">

                {{-- Encabezado: sale de Ajustes del sitio, así que es el mismo en todos los envíos. --}}
                <tr>
                    <td align="center" style="padding:28px 24px 22px 24px; border-bottom:3px solid #259ACF;">
                        <a href="{{ $site['url'] }}" style="text-decoration:none; color:#222222;">
                            @if ($site['logo'])
                                <img src="{{ $site['logo'] }}" alt="{{ $site['name'] }}" width="96"
                                     style="display:block; margin:0 auto 12px auto; width:96px; max-width:96px; height:auto; border:0;" />
                            @endif
                            <span style="display:block; font-size:20px; line-height:26px; font-weight:bold; color:#259ACF; letter-spacing:0.5px;">
                                {{ $site['name'] }}
                            </span>
                        </a>
                    </td>
                </tr>

                @forelse ($items as $item)
                    <tr>
                        <td style="padding:28px 24px; border-bottom:1px solid #D1DAE5;">

                            @if ($item['image'])
                                <a href="{{ $item['cta_url'] }}" style="text-decoration:none;">
                                    <img src="{{ $item['image'] }}" alt="{{ $item['title'] }}" width="552"
                                         style="display:block; width:100%; max-width:552px; height:auto; border:0; border-radius:8px; margin-bottom:18px;" />
                                </a>
                            @endif

                            <h2 style="margin:0 0 6px 0; font-size:24px; line-height:30px; font-weight:normal; color:#259ACF;">
                                {{ $item['title'] }}
                            </h2>

                            @if ($item['teachers'])
                                <p style="margin:0 0 14px 0; font-size:15px; line-height:20px; color:#CD6023;">
                                    {{ $item['teachers'] }}
                                </p>
                            @endif

                            {{--
                                Cuándo, dónde y cuánto, cada dato en su renglón. En el sitio
                                esto son iconos; acá son palabras, porque un icono en un mail
                                es una imagen más que puede no cargar.
                            --}}
                            @if ($item['when'] || $item['location'] || $item['price'])
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 14px 0;">
                                    @foreach (['when' => 'Cuándo', 'location' => 'Dónde', 'price' => 'Precio'] as $key => $label)
                                        @if ($item[$key])
                                            <tr>
                                                <td style="padding:2px 10px 2px 0; font-size:14px; line-height:20px; color:#7A7A7A; white-space:nowrap; vertical-align:top;">
                                                    {{ $label }}
                                                </td>
                                                <td style="padding:2px 0; font-size:15px; line-height:20px; color:#54595F; font-weight:bold;">
                                                    {{ $item[$key] }}
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </table>
                            @endif

                            @foreach (\App\Support\NewsletterRenderer::paragraphs($item['body']) as $paragraph)
                                <p style="margin:0 0 12px 0; font-size:15px; line-height:23px; color:#54595F;">{{ $paragraph }}</p>
                            @endforeach

                            @if ($item['cta_url'])
                                {{-- Botón de tabla y no un <a> con padding: Outlook ignora el padding. --}}
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:8px;">
                                    <tr>
                                        <td align="center" bgcolor="#CD6023" style="border-radius:24px;">
                                            <a href="{{ $item['cta_url'] }}"
                                               style="display:inline-block; padding:11px 26px; font-size:14px; line-height:18px; font-weight:bold; color:#ffffff; text-decoration:none; text-transform:uppercase; letter-spacing:0.5px;">
                                                {{ $item['cta_label'] }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                        </td>
                    </tr>
                @empty
                    <tr>
                        <td style="padding:28px 24px; font-size:15px; line-height:23px; color:#7A7A7A;">
                            (Este newsletter todavía no tiene ninguna actividad elegida.)
                        </td>
                    </tr>
                @endforelse

                {{-- Pie: los mismos datos que el pie del sitio, más lo que exige Mailchimp. --}}
                <tr>
                    <td align="center" style="padding:26px 24px; background-color:#FFF1E6;">

                        <p style="margin:0 0 10px 0; font-size:14px; line-height:22px; color:#54595F;">
                            @if ($site['email'])
                                <a href="mailto:{{ $site['email'] }}" style="color:#259ACF; text-decoration:none;">{{ $site['email'] }}</a>
                            @endif
                            @if ($site['phone'])
                                &nbsp;·&nbsp;
                                <a href="{{ $site['phone_link'] ?: 'tel:'.$site['phone'] }}" style="color:#259ACF; text-decoration:none;">{{ $site['phone'] }}</a>
                            @endif
                            @if ($site['instagram'])
                                &nbsp;·&nbsp;
                                <a href="{{ $site['instagram'] }}" style="color:#259ACF; text-decoration:none;">Instagram</a>
                            @endif
                        </p>

                        @if ($site['address'])
                            <p style="margin:0 0 10px 0; font-size:13px; line-height:20px; color:#7A7A7A;">{{ $site['address'] }}</p>
                        @endif

                        <p style="margin:0 0 14px 0; font-size:13px; line-height:20px; color:#7A7A7A;">
                            <a href="{{ $site['url'] }}" style="color:#259ACF; text-decoration:none;">{{ $site['name'] }}</a>
                        </p>

                        <p style="margin:0; font-size:12px; line-height:19px; color:#7A7A7A;">
                            Recibís este correo porque te suscribiste a nuestras novedades.<br />
                            <a href="*|UNSUB|*" style="color:#7A7A7A; text-decoration:underline;">Darse de baja</a>
                            &nbsp;·&nbsp;
                            <a href="*|UPDATE_PROFILE|*" style="color:#7A7A7A; text-decoration:underline;">Cambiar tus preferencias</a>
                        </p>

                        <div style="margin-top:12px; font-size:12px; line-height:19px; color:#7A7A7A;">
                            *|HTML:LIST_ADDRESS_HTML|*
                        </div>

                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
