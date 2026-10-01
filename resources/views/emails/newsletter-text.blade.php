{{--
    La versión en texto plano, para el cliente de correo que no muestra HTML.

    Se escribe a mano en vez de dejar que Mailchimp la genere sola: lo que él arma a
    partir de un HTML lleno de tablas anidadas sale ilegible, con los datos mezclados
    y los renglones cortados donde no va.

    El enlace de baja también va acá: el mail en texto plano tiene que poder darse de
    baja igual que el otro.
--}}@php
    $line = fn (?string $label, ?string $value) => filled($value) ? "{$label}: {$value}\n" : '';
@endphp
{{ $site['name'] }}
{{ str_repeat('=', mb_strlen($site['name'])) }}
@if (filled($newsletter->preview_text))

{{ $newsletter->preview_text }}
@endif
@foreach ($items as $item)

--------------------------------------------------

{{ $item['title'] }}
@if ($item['teachers']){{ $item['teachers'] }}
@endif
{{ $line('Cuándo', $item['when']) }}{{ $line('Dónde', $item['location']) }}{{ $line('Precio', $item['price']) }}
@foreach (\App\Support\NewsletterRenderer::paragraphs($item['body']) as $paragraph)
{{ $paragraph }}

@endforeach
@if ($item['cta_url']){{ $item['cta_label'] }}: {{ $item['cta_url'] }}
@endif
@endforeach

--------------------------------------------------

{{ $site['name'] }} — {{ $site['url'] }}
{{ $line('Email', $site['email']) }}{{ $line('Teléfono', $site['phone']) }}{{ $line('Instagram', $site['instagram']) }}{{ $line('Dirección', $site['address']) }}
Recibís este correo porque te suscribiste a nuestras novedades.
Darse de baja: *|UNSUB|*

*|HTML:LIST_ADDRESS_HTML|*
