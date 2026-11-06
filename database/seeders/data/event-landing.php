<?php

/**
 * La plantilla genérica de la landing de evento (Link interno), con el contenido
 * de muestra de https://meditarenperu.org/eventos-especiales/iniciacion-de-tara-verde
 * (2026-11-06). Cada evento recibe una copia que el dueño edita; esta plantilla se
 * edita desde Admin › Plantilla de landing y sólo afecta a las landings nuevas.
 *
 * Las imágenes viven en database/seeders/images/landing-evento y se publican en
 * storage como seed/landing-evento/… (nunca se borran: se comparten entre copias).
 *
 * {site_name} y {address} se reemplazan con los Ajustes del sitio al sembrar.
 */
return [
    'title' => 'Plantilla de landing de evento',
    'sections' => [
        [
            'type' => 'landing_hero',
            'key' => 'banner',
            'content' => [
                'image' => 'landing-evento/tara-verde.jpg',
                'eyebrow' => '{site_name}',
                'heading' => 'Iniciación de',
                'heading_accent' => 'Tara Verde',
                'subheading' => 'Un encuentro especial para cultivar protección, confianza y paz interior.',
                'date' => '04–06 diciembre 2026',
                'place' => '{address}',
                'cta_label' => 'Reserva tu lugar',
                'cta_url' => null,
            ],
        ],
        [
            'type' => 'landing_intro',
            'key' => 'que-es',
            'content' => [
                'kicker' => 'Una experiencia especial',
                'heading' => '¿Qué es una iniciación?',
                'body' => "Una iniciación es una meditación guiada muy especial en la que recibimos bendiciones profundas e inspiración espiritual. Esta energía positiva despierta nuestro potencial interior de paz, sabiduría y felicidad.\n\nEs una experiencia sencilla y profundamente significativa, accesible para cualquier persona. No se requieren conocimientos previos ni experiencia en meditación. Solo se necesita una actitud receptiva y el deseo genuino de cultivar paz interior, claridad y un buen corazón.",
                'cards' => [
                    ['image' => null, 'title' => 'Conexión espiritual', 'text' => 'Nos ayuda a establecer un vínculo especial con un Buda.', 'url' => null],
                    ['image' => null, 'title' => 'Inspiración', 'text' => 'Nos da fuerza para comenzar o profundizar nuestro camino espiritual.', 'url' => null],
                    ['image' => null, 'title' => 'Transformación interior', 'text' => 'Siembra una semilla para cultivar paz, compasión y sabiduría.', 'url' => null],
                ],
            ],
        ],
        [
            'type' => 'landing_about',
            'key' => 'sobre-el-evento',
            'content' => [
                'image' => 'landing-evento/tara-verde.jpg',
                'image_side' => 'left',
                'kicker' => 'La Buda liberadora',
                'heading' => 'Iniciación de Tara Verde',
                'body' => "En este evento recibiremos la iniciación de Tara Verde, una ceremonia profundamente amorosa y transformadora.\n\nTara Verde es un Buda femenino, la manifestación de la sabiduría compasiva de todos los Budas. Su nombre significa “Liberadora”, porque nos ayuda a liberarnos de los miedos internos y externos, de los obstáculos mentales y del sufrimiento cotidiano.\n\nComo una madre, Tara nos protege, nos cuida y nos guía con rapidez cuando confiamos en ella con sinceridad.",
            ],
        ],
        [
            'type' => 'landing_quote',
            'key' => 'frase',
            'content' => [
                'quote' => 'Si confiamos en nuestra Madre Tara con sinceridad y fe firme, nos protegerá de todos los obstáculos y cumplirá todos nuestros deseos. Debido a que es un Buda de la sabiduría y puesto que es una manifestación del elemento aire completamente purificado, Tara es capaz de ayudarnos con gran rapidez.',
                'author' => 'Gueshe Kelsang Gyatso, 1993',
            ],
        ],
        [
            'type' => 'landing_benefits',
            'key' => 'beneficios',
            'content' => [
                'kicker' => 'Paz, confianza y sabiduría',
                'heading' => 'Beneficios de recibir la iniciación',
                'intro' => 'Recibir la iniciación de Tara Verde nos permite:',
                'items' => [
                    'Conectar con una fuente profunda de protección y confianza interior.',
                    'Desarrollar mentes virtuosas que producen paz y claridad.',
                    'Fortalecer nuestra capacidad de responder con sabiduría ante los desafíos de la vida.',
                    'Reducir miedos, ansiedad y demás perturbaciones mentales.',
                    'Avanzar con mayor rapidez en el camino del crecimiento personal y espiritual.',
                ],
                'closing' => 'Al crear este vínculo especial con Tara Verde, sembramos una semilla muy poderosa en nuestro corazón: la capacidad de cuidar de nuestra mente con amor y de beneficiar a los demás de manera cada vez más genuina.',
            ],
        ],
        [
            'type' => 'landing_teacher',
            'key' => 'maestro',
            'content' => [
                'image' => 'landing-evento/maestra.jpeg',
                'kicker' => 'La maestra',
                'heading' => 'Guen Kelsang Namdrol',
                'body' => "Maestra residente del Centro de Meditación Kadampa Perú, quien otorgará la iniciación de Tara Verde. Guen Namdrol es una monja budista Kadampa originaria de México. Practica el budismo Kadampa desde hace 20 años y recibió la ordenación de parte de su Guía Espiritual, el Venerable Gueshe Kelsang Gyatso Rimpoché, en Inglaterra en 2009.\n\nAl impartir las enseñanzas de Buda, Guen Namdrol deja ver su humildad y sinceridad. Sus clases son dinámicas y muy prácticas, pues habla de su propia experiencia al aplicar las enseñanzas en su vida diaria.",
            ],
        ],
        [
            'type' => 'landing_program',
            'key' => 'programa',
            'content' => [
                'kicker' => '04 · 05 · 06 diciembre 2026',
                'heading' => 'Programa del evento',
                'intro' => 'Tres días para meditar, recibir enseñanzas y profundizar en la práctica.',
                'days' => [
                    [
                        'weekday' => 'Viernes',
                        'day' => '04',
                        'featured' => false,
                        'items' => [
                            ['time' => '19:00 – 20:30', 'name' => 'Introducción', 'highlight' => false],
                        ],
                    ],
                    [
                        'weekday' => 'Sábado',
                        'day' => '05',
                        'featured' => true,
                        'items' => [
                            ['time' => '09:30 – 10:30', 'name' => 'Meditación 1', 'highlight' => false],
                            ['time' => '11:30 – 13:30', 'name' => 'Iniciación de Tara Verde', 'highlight' => true],
                            ['time' => '16:00 – 17:30', 'name' => 'Enseñanza 1', 'highlight' => false],
                            ['time' => '18:30 – 19:45', 'name' => 'Gema del corazón con tsog', 'highlight' => false],
                        ],
                    ],
                    [
                        'weekday' => 'Domingo',
                        'day' => '06',
                        'featured' => false,
                        'items' => [
                            ['time' => '10:00 – 11:00', 'name' => 'Meditación 2', 'highlight' => false],
                            ['time' => '11:30 – 13:00', 'name' => 'Enseñanza 2', 'highlight' => false],
                        ],
                    ],
                ],
            ],
        ],
        [
            'type' => 'landing_booking',
            'key' => 'reserva',
            'content' => [
                'kicker' => 'Participa',
                'heading' => 'Reserva tu lugar',
                'intro' => 'Iniciación de Tara Verde · Del viernes 04 al domingo 06 de diciembre de 2026',
                'price_label' => 'Contribución',
                'prices' => [
                    ['note' => 'Precio promocional hasta el jueves 26 de noviembre', 'amount' => '$ 200'],
                    ['note' => 'Desde el viernes 27 de noviembre', 'amount' => '$ 250'],
                ],
                'cta_label' => 'Inscribirme',
                'cta_url' => null,
                'place_label' => 'Lugar',
                'venue' => '{site_name}',
                'address' => '{address}',
                'map_query' => null,
            ],
        ],
    ],
];
