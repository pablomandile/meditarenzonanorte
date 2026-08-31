<?php

/**
 * Site settings cloned from https://meditarenrosario.org/ (2026-07-23).
 * phone_display is the header button text; the footer shows "+54 341 6989430".
 * whatsapp_url is just the number (from the Click to Chat plugin config found in
 * the HTML: 5493416989430); the pre-filled message lives apart, in
 * whatsapp_message, and App\Support\WhatsApp joins the two into one link.
 */
return [
    'site_name' => 'Meditación Kadampa Rosario',
    'phone_display' => '341 6 989430',
    'phone_link' => 'tel:+543416989430',
    'whatsapp_url' => 'https://wa.me/5493416989430',
    'whatsapp_message' => 'Hola me gustaría recibir info sobre las actividades.',
    'email' => 'meditarenrosario@gmail.com',
    'instagram_url' => 'https://www.instagram.com/meditarenrosario/',
    'address' => 'Psj. Cajaraville 173, Barrio Martin, Rosario',
    'logo' => 'shared/cropped-cropped-Rosario-logo-negro.png',
];
