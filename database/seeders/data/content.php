<?php

/**
 * Content cloned from https://meditarenrosario.org/ (2026-07-23).
 * Image paths are relative to the images/ directory of the clone.
 * Body fields are plain text, paragraphs separated by "\n\n".
 */
return [

    'home' => [
        'title' => 'Meditación y Budismo Moderno',
        'menu_label' => null,
        'menu_order' => 0,
        'meta_description' => 'Actividades semanales Clase semanal online Meditaciones guiadas gratuitas Meditación Kadampa Rosario Meditación Kadampa Rosario forma parte de la Nueva',
        'sections' => [
            [
                'type' => 'hero',
                'key' => 'hero',
                'content' => [
                    'image' => 'home/Meditacion-y-Budismo-Moderno.jpg',
                    'heading' => null,
                    'subheading' => null,
                ],
            ],
            [
                'type' => 'card_grid',
                'key' => 'actividades-semanales',
                'content' => [
                    'heading' => 'Actividades semanales',
                    'cards' => [
                        [
                            'image' => 'home/62.jpg',
                            'title' => 'Clase semanal online',
                            'text' => null,
                            'url' => '/clases-semanales',
                        ],
                        [
                            'image' => 'home/63-1.jpg',
                            'title' => 'Meditaciones guiadas gratuitas',
                            'text' => null,
                            'url' => '/gratis',
                        ],
                    ],
                ],
            ],
            [
                'type' => 'text_block',
                'key' => 'intro',
                'content' => [
                    'heading' => 'Meditación Kadampa Rosario',
                    'body' => 'Meditación Kadampa Rosario forma parte de la Nueva Tradición Kadampa, una comunidad espiritual internacional dedicada al logro de la paz mundial siguiendo el camino budista kadampa. Está ubicado en Barrio Martin, a metros del Parque Urquiza, en el Pasaje Cajaraville 173 y es un anexo del centro principal ubicado en Buenos Aires (Palermo, Serrano 1316).',
                    'image' => 'home/Imagen_home_CMKA.webp',
                    'image_side' => 'right',
                    'links' => [
                        ['label' => 'Nueva Tradición Kadampa', 'url' => 'https://kadampa.org/es'],
                        ['label' => 'Barrio Martin, a metros del Parque Urquiza, en el Pasaje Cajaraville 173', 'url' => 'https://maps.app.goo.gl/dj2DJQ1Cow79uk359'],
                        ['label' => 'Visita de Kelsang Panchen', 'url' => '/eventos-especiales'],
                        ['label' => 'Actividades gratis', 'url' => '/gratis'],
                        ['label' => 'Clases semanales', 'url' => '/clases-semanales'],
                    ],
                ],
            ],
            [
                'type' => 'text_image',
                'key' => 'fundador',
                'content' => [
                    'heading' => 'El fundador',
                    'body' => 'El Fundador del Budismo Kadampa Moderno, el venerable Gueshe Kelsang Gyatso Rimpoché, maestro de meditación y Maestro de renombre mundial, inspiró a miles de personas de todo el mundo a aplicar las enseñanzas puras de Buda a sus vidas modernas para resolver sus problemas personales y descubrir una paz interior y una felicidad profundas y duraderas.',
                    'image' => 'home/18.jpg',
                    'image_side' => 'right',
                    'link_label' => 'Más información',
                    'link_url' => 'https://kadampa.org/es/venerable-gueshe-kelsang-gyatso',
                ],
            ],
            [
                'type' => 'quote',
                'key' => 'testimonio',
                'content' => [
                    'quote' => 'Me encantó participar en la meditación , muy cálidos y amables todos por ser la primera vez que asistía, salí mas liviana mas ligera y queriendo volver.',
                    'author' => 'Mariel Aguirre',
                ],
            ],
            [
                'type' => 'event_strip',
                'key' => 'proximamente',
                'content' => [
                    'heading' => 'Eventos especiales en Rosario',
                ],
            ],
            [
                'type' => 'card_grid',
                'key' => 'festivales',
                'content' => [
                    'heading' => 'Eventos Nacionales e Internacionales',
                    'cards' => [
                        [
                            'image' => 'shared/Festival-internacional-de-Verano-UK.jpg',
                            'title' => null,
                            'text' => null,
                            'url' => 'https://kadampafestivals.org/es/verano',
                        ],
                        [
                            'image' => 'shared/Festival-internacional-de-otono.jpg',
                            'title' => null,
                            'text' => null,
                            'url' => 'https://kadampafestivals.org/es/otono',
                        ],
                        [
                            'image' => 'shared/Festival-del-Dharma-buenos-aires-2026.jpg',
                            'title' => null,
                            'text' => null,
                            'url' => 'https://meditarenargentina.org/eventos-nacionales/',
                        ],
                    ],
                ],
            ],
            [
                'type' => 'map',
                'key' => 'donde-estamos',
                'content' => [
                    'heading' => '¿Dónde estamos?',
                    'query' => 'Meditar en Rosario Cajaraville 173, S2000 Rosario, Santa Fe',
                ],
            ],
            [
                'type' => 'card_grid',
                'key' => 'recursos',
                'content' => [
                    'heading' => null,
                    'cards' => [
                        [
                            'image' => 'shared/Spanish-KBW-2026.jpeg.webp',
                            'title' => 'EL BUDISMO KADAMPA',
                            'text' => 'Programa internacional de enseñanzas',
                            'url' => 'https://kadampa.org/es/kadampa-buddhism-worldwide-brochure',
                        ],
                        [
                            'image' => 'shared/CTTV-Pack-3D-2017-web.png',
                            'title' => 'CÓMO TRANSFORMAR TU VIDA',
                            'text' => 'descargalo gratis aquí',
                            'url' => 'https://comotransformartuvida.com',
                        ],
                        [
                            'image' => 'shared/bm2-2.png',
                            'title' => 'BUDISMO MODERNO',
                            'text' => 'descargalo gratis aquí',
                            'url' => 'https://budismomoderno.com',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'clases-semanales' => [
        'title' => 'Clases semanales',
        'menu_label' => 'Clases semanales',
        'menu_order' => 1,
        'meta_description' => 'ACTIVIDADES SEMANALES Clases semanalesLibertad emocional con Kelsang Panchen Todos los miércoles, nos encontramos a mirar juntos en video las clases que',
        'sections' => [
            [
                'type' => 'page_header',
                'key' => 'titulo',
                'content' => [
                    'heading' => 'ACTIVIDADES SEMANALES',
                    'intro' => null,
                    'style' => 'orange',
                ],
            ],
            [
                // Misma portada que 'eventos-especiales' y 'cursos-y-retiros'.
                'type' => 'hero',
                'key' => 'banner',
                'content' => [
                    'image' => 'eventos-especiales/vista-julio-rosario-kelsang-panchen7.jpg',
                    'heading' => null,
                    'subheading' => null,
                ],
            ],
            [
                'type' => 'class_info',
                'key' => 'clase-principal',
                'content' => [
                    'heading' => "Clases semanales\nLibertad emocional con Kelsang Panchen",
                    'body' => "Todos los miércoles, nos encontramos a mirar juntos en video las clases que Kelsang Panchen dicta en el Centro de Meditación Kadampa de Buenos Aires. En junio y julio veremos claves para vínculos más sanos.\n\n*No es necesario experiencia ni inscripción previa.\n\n¡TODO EL MUNDO ES BIENVENIDO!",
                    'schedule' => 'Miércoles de 19 a 20.15 hs',
                    // La lectura de 'schedule' en clave de calendario: 3 = miércoles (ISO).
                    'occurrences' => [
                        ['type' => 'weekly', 'weekday' => 3, 'date' => null, 'from' => null, 'until' => null, 'start' => '19:00', 'end' => '20:15', 'label' => null],
                    ],
                    'location' => 'Psj. Cajaraville 173, Barrio Martin, Rosario',
                    'price' => '$5.000 (Bono 4 clases $15.000)',
                    'cta_label' => 'INSCRIPCIÓN TARJETAS KADAMPA',
                    'cta_url' => 'https://meditarenargentina.org/tarjeta-kadampa/',
                    'image' => 'clases-semanales/5.jpg',
                ],
            ],
            [
                'type' => 'text_image',
                'key' => 'maestro',
                'content' => [
                    'heading' => 'Kelsang Panchen',
                    'body' => "Maestro Principal de Rosario\n\nEs el segundo maestro residente del Centro de Meditación Kadampa Argentina. Encontró las enseñanzas de Buda a los 23 años y al poco tiempo se ordenó como monje kadampa. Realizó su formación como maestro en el ITTP (International Intensive Training Program) en el Templo de Manyushri (Inglaterra) y en el Centro de Meditación Kadampa Barcelona. Sus enseñanzas son cercanas a la gente y de gran calidez. Es reconocido por su serenidad y claridad al transmitir las enseñanzas de Buda. Kelsang Panchen, es un ejemplo muy inspirador.",
                    'image' => 'shared/K_Panchen.webp',
                    'image_side' => 'right',
                    'link_label' => null,
                    'link_url' => null,
                ],
            ],
            [
                'type' => 'bullet_list',
                'key' => 'detalles-clase',
                'content' => [
                    'heading' => 'Detalles de las clases',
                    'intro' => '¿Qué temas se tratan en las sesiones?',
                    'items' => [
                        'Técnicas para el aprendizaje de la meditación y profundización en tu experiencia.',
                        'Meditaciones guiadas en todas las sesiones.',
                        'Enseñanzas prácticas sobre cómo aplicar la antigua sabiduría de Buda en nuestra ajetreada vida moderna.',
                        'Aunque el aprendizaje es progresivo, podés sumarte en cualquier momento.',
                    ],
                ],
            ],
            [
                'type' => 'text_block',
                'key' => 'estructura-clase',
                'content' => [
                    'heading' => 'Estructura de una sesión',
                    'body' => "¿Qué esperar de una clase?\n\nMeditación guiada\n\nCada sesión comienza con una meditación en la respiración, para calmar la mente, eliminar las distracciones y cultivar paz interior.\n\nEnseñanza\n\nA continuación, una explicación de los métodos prácticos que provienen de las enseñanzas de Buda para ser felices en nuestro día a día, solucionar nuestros problemas internos y cultivar estados mentales apacibles.\n\nMeditación final\n\nLas sesiones terminan con una segunda meditación basada en las ideas aprendidas y consejos para integrarlos en nuestra vida cotidiana.",
                    'links' => [],
                ],
            ],
            [
                'type' => 'class_info',
                'key' => 'meditaciones-gratuitas',
                'content' => [
                    'heading' => "Meditaciones guiadas\nen 30 minutos",
                    'body' => null,
                    'schedule' => "Martes y jueves\n18 a 18.30hs",
                    'occurrences' => [
                        ['type' => 'weekly', 'weekday' => 2, 'date' => null, 'from' => null, 'until' => null, 'start' => '18:00', 'end' => '18:30', 'label' => null],
                        ['type' => 'weekly', 'weekday' => 4, 'date' => null, 'from' => null, 'until' => null, 'start' => '18:00', 'end' => '18:30', 'label' => null],
                    ],
                    'location' => 'Psj. Cajaraville 173, Barrio Martin, Rosario',
                    'price' => 'GRATUITAS',
                    'cta_label' => 'MÁS INFORMACIÓN',
                    'cta_url' => '/gratis',
                    'image' => 'clases-semanales/web-rosario-2.jpg',
                ],
            ],
            [
                'type' => 'faq',
                'key' => 'faq',
                'content' => [
                    'heading' => 'Preguntas frecuententes',
                    'faq_refs' => [0, 1, 2, 3, 4, 5],
                ],
            ],
        ],
    ],

    'eventos-especiales' => [
        'title' => 'Eventos especiales',
        'menu_label' => 'Eventos especiales',
        'menu_order' => 2,
        'meta_description' => 'EVENTOS ESPECIALES CINE Y MEDITACIÓN Película: La vida de Buda Una obra especial, inspirada por el venerable Gueshe Kelsang Gyatso y sus enseñanzas, que recoge',
        'sections' => [
            [
                'type' => 'page_header',
                'key' => 'titulo',
                'content' => [
                    'heading' => 'EVENTOS ESPECIALES',
                    'intro' => null,
                    'style' => 'sky',
                ],
            ],
            [
                'type' => 'hero',
                'key' => 'banner',
                'content' => [
                    'image' => 'eventos-especiales/vista-julio-rosario-kelsang-panchen7.jpg',
                    'heading' => null,
                    'subheading' => null,
                ],
            ],
            [
                'type' => 'event_list',
                'key' => 'eventos',
                'content' => [
                    'heading' => null,
                    'empty_text' => 'próximamente',
                ],
            ],
            [
                'type' => 'text_block',
                'key' => 'bienvenida',
                'content' => [
                    'heading' => '¡Todo el mundo es bienvenido!',
                    'body' => null,
                    'links' => [],
                ],
            ],
            [
                'type' => 'text_image',
                'key' => 'maestro',
                'content' => [
                    'heading' => 'Kelsang Panchen',
                    'body' => "Maestro Principal de Rosario\n\nEs el segundo maestro residente del Centro de Meditación Kadampa Argentina. Encontró las enseñanzas de Buda a los 23 años y al poco tiempo se ordenó como monje kadampa. Realizó su formación como maestro en el ITTP (International Intensive Training Program) en el Templo de Manyushri (Inglaterra) y en el Centro de Meditación Kadampa Barcelona. Sus enseñanzas son cercanas a la gente y de gran calidez. Es reconocido por su serenidad y claridad al transmitir las enseñanzas de Buda. Kelsang Panchen, es un ejemplo muy inspirador.",
                    'image' => 'shared/K_Panchen.webp',
                    'image_side' => 'right',
                    'link_label' => null,
                    'link_url' => null,
                ],
            ],
            [
                'type' => 'card_grid',
                'key' => 'festivales',
                'content' => [
                    'heading' => 'NACIONALES E INTERNACIONALES',
                    'cards' => [
                        [
                            'image' => 'shared/Festival-internacional-de-Verano-UK.jpg',
                            'title' => null,
                            'text' => null,
                            'url' => 'https://kadampafestivals.org/es/verano',
                        ],
                        [
                            'image' => 'shared/Festival-internacional-de-otono.jpg',
                            'title' => null,
                            'text' => null,
                            'url' => 'https://kadampafestivals.org/es/otono',
                        ],
                        [
                            'image' => 'shared/Festival-del-Dharma-buenos-aires-2026.jpg',
                            'title' => null,
                            'text' => null,
                            'url' => 'https://meditarenargentina.org/eventos-nacionales/',
                        ],
                    ],
                ],
            ],
            [
                'type' => 'map',
                'key' => 'donde-estamos',
                'content' => [
                    'heading' => '¿Dónde estamos?',
                    'query' => 'Meditar en Rosario Cajaraville 173, S2000 Rosario, Santa Fe',
                ],
            ],
            [
                'type' => 'bullet_list',
                'key' => 'detalles-clase',
                'content' => [
                    'heading' => 'Detalles de las clases',
                    'intro' => '¿Qué temas se tratan en las sesiones?',
                    'items' => [
                        'Técnicas para el aprendizaje de la meditación y profundización en tu experiencia.',
                        'Meditaciones guiadas en todas las sesiones.',
                        'Enseñanzas prácticas sobre cómo aplicar la antigua sabiduría de Buda en nuestra ajetreada vida moderna.',
                        'Aunque el aprendizaje es progresivo, podés sumarte en cualquier momento.',
                    ],
                ],
            ],
            [
                'type' => 'text_block',
                'key' => 'estructura-clase',
                'content' => [
                    'heading' => 'Estructura de una sesión',
                    'body' => "¿Qué esperar de una clase?\n\nMeditación guiada\n\nCada sesión comienza con una meditación en la respiración, para calmar la mente, eliminar las distracciones y cultivar paz interior.\n\nEnseñanza\n\nA continuación, una explicación de los métodos prácticos que provienen de las enseñanzas de Buda para ser felices en nuestro día a día, solucionar nuestros problemas internos y cultivar estados mentales apacibles.\n\nMeditación final\n\nLas sesiones terminan con una segunda meditación basada en las ideas aprendidas y consejos para integrarlos en nuestra vida cotidiana.",
                    'links' => [],
                ],
            ],
            [
                'type' => 'faq',
                'key' => 'faq',
                'content' => [
                    'heading' => 'Preguntas frecuententes',
                    'faq_refs' => [0, 1, 2, 3, 4, 5],
                ],
            ],
        ],
    ],

    'gratis' => [
        'title' => 'Gratis',
        'menu_label' => 'Gratis',
        'menu_order' => 3,
        'meta_description' => 'ACTIVIDADES GRATUITAS Meditaciones guiadasen 30 minutos Meditaciones guiadasen 30 minutos Muchas veces, en el ritmo acelerado del día a día, sentimos que no',
        'sections' => [
            [
                'type' => 'page_header',
                'key' => 'titulo',
                'content' => [
                    'heading' => 'ACTIVIDADES GRATUITAS',
                    'intro' => null,
                    'style' => 'sky',
                ],
            ],
            [
                'type' => 'class_info',
                'key' => 'oferta-miercoles-jueves',
                'content' => [
                    'heading' => "Meditaciones guiadas\nen 30 minutos",
                    'body' => null,
                    'schedule' => "Miércoles y jueves\n18 a 18.30hs",
                    'occurrences' => [
                        ['type' => 'weekly', 'weekday' => 3, 'date' => null, 'from' => null, 'until' => null, 'start' => '18:00', 'end' => '18:30', 'label' => null],
                        ['type' => 'weekly', 'weekday' => 4, 'date' => null, 'from' => null, 'until' => null, 'start' => '18:00', 'end' => '18:30', 'label' => null],
                    ],
                    'location' => 'Psj. Cajaraville 173, Barrio Martin, Rosario',
                    'price' => 'GRATUITAS',
                    'cta_label' => null,
                    'cta_url' => null,
                    'image' => 'gratis/MEDITACIONES-GUIDAS-EN-30-MINUTOS-GRATIS-ROSARIO.jpg',
                ],
            ],
            [
                // Los sábados a la mañana, para que sea una segunda oferta y no la
                // misma actividad dos veces: tal como venía del sitio original,
                // esta ficha repetía martes y jueves de 18, igual que la de
                // 'clases-semanales', y en la página se leían dos tarjetas idénticas.
                'type' => 'class_info',
                'key' => 'oferta-sabados',
                'content' => [
                    'heading' => "Meditaciones guiadas\nen 30 minutos",
                    'body' => null,
                    'schedule' => "Sábados\n10 a 10.30hs",
                    'occurrences' => [
                        ['type' => 'weekly', 'weekday' => 6, 'date' => null, 'from' => null, 'until' => null, 'start' => '10:00', 'end' => '10:30', 'label' => null],
                    ],
                    'location' => 'Psj. Cajaraville 173, Barrio Martin, Rosario',
                    'price' => 'GRATUITAS',
                    'cta_label' => null,
                    'cta_url' => null,
                    'image' => 'gratis/64.jpg',
                ],
            ],
            [
                'type' => 'text_image',
                'key' => 'invitacion',
                'content' => [
                    'heading' => null,
                    'body' => "Muchas veces, en el ritmo acelerado del día a día, sentimos que no tenemos tiempo para nada, y mucho menos para meditar. Sin embargo, dedicar apenas 30 minutos a la meditación puede marcar una gran diferencia. Incorporarla, nos ayuda a soltar tensiones y a reducir preocupaciones.\n\nSumate a nuestras meditaciones guiadas paso a paso por practicantes budistas y comenzá a disfrutar de los beneficios de la meditación. ¡Es fácil y accesible para todos!",
                    'image' => 'gratis/MEDITACIONES-GUIDAS-EN-30-MINUTOS-GRATIS-ROSARIO2.jpg',
                    'image_side' => 'left',
                    'link_label' => null,
                    'link_url' => null,
                ],
            ],
            [
                'type' => 'bullet_list',
                'key' => 'beneficios',
                'content' => [
                    'heading' => 'Beneficios de la meditación:',
                    'intro' => null,
                    'items' => [
                        'Experimentá profunda relajación',
                        'Mejorá tu salud física y mental',
                        'Reducí el estrés y la ansiedad',
                        'Solta y viví más liger@',
                    ],
                ],
            ],
            [
                'type' => 'text_block',
                'key' => 'bienvenida',
                'content' => [
                    'heading' => '¡Todo el mundo es bienvenido!',
                    'body' => null,
                    'links' => [],
                ],
            ],
            [
                'type' => 'faq',
                'key' => 'faq',
                'content' => [
                    'heading' => 'Preguntas frecuententes',
                    'faq_refs' => [0, 1, 2, 3, 4, 5],
                ],
            ],
        ],
    ],

    'quienes-somos' => [
        'title' => '¿Quienes somos?',
        'menu_label' => '¿Quienes somos?',
        'menu_order' => 4,
        'meta_description' => '¿Quienes somos? El budismo kadampa es una tradición de budismo mahayana fundada por el gran maestro indio Atisha (982-1054). Ka se refiere a todas las',
        'sections' => [
            [
                'type' => 'hero',
                'key' => 'banner',
                'content' => [
                    'image' => 'quienes-somos/Header-homepage-new-kadampa-tradition.jpg',
                    'heading' => null,
                    'subheading' => null,
                ],
            ],
            [
                'type' => 'text_block',
                'key' => 'kadampa',
                'content' => [
                    'heading' => '¿Quienes somos?',
                    'body' => "El budismo kadampa es una tradición de budismo mahayana fundada por el gran maestro indio Atisha (982-1054).\n\nKa se refiere a todas las enseñanzas de Buda, y dam, a las instrucciones especiales del Lamrim, las etapas del camino hacia la iluminación, que Atisha enseñó. Por lo tanto, los practicantes de budismo kadampa integran en el Lamrim todas las enseñanzas de Buda que han aprendido tomándolas como un consejo personal y poniéndolas en práctica.\n\nLos budistas kadampas integran su conocimiento de todas las enseñanzas de Buda en su práctica del Lamrim, y ésta en su vida diaria. De este modo, transforman todas sus actividades en el camino hacia la iluminación.\n\nEl fundador del budismo kadampa moderno es el venerable Gueshela Kelsang Gyatso Rimpoché, conocido cariñosamente como venerable Gueshela, maestro de meditación y maestro de renombre mundial. Inspiró a miles de personas de todo el mundo a aplicar las enseñanzas puras de Buda a sus vidas modernas para resolver sus problemas personales y descubrir una paz interior y una felicidad profundas y duraderas. En la actualidad, el legado de su obra llega a personas de todas las nacionalidades y culturas.",
                    'links' => [
                        ['label' => 'Atisha', 'url' => 'https://kadampa.org/es/buddhism/atisha4'],
                        ['label' => 'enseñanzas de Buda', 'url' => 'https://kadampa.org/es/reference/las-ensenanzas-de-buda'],
                        ['label' => 'Lamrim', 'url' => 'https://kadampa.org/es/buddhism/etapas-del-camino'],
                        ['label' => 'Seguir leyendo', 'url' => 'https://kadampa.org/es'],
                    ],
                ],
            ],
            [
                'type' => 'person',
                'key' => 'fundador',
                'content' => [
                    'role' => 'Fundador',
                    'name' => 'Venerable Gueshe Kelsang Gyatso Rimpoché',
                    'subtitle' => 'Maestro de meditación reconocido mundialmente',
                    'body' => "El fundador del Budismo Kadampa Moderno es el venerable Gueshe Kelsang Gyatso, maestro de meditación mundialmente reconocido y que sostiene la esencia de las enseñanzas de Buda en su corazón.\n\nEl venerable Gueshela transmite esta sabiduría profunda y compasión a la gente del mundo moderno en modos muy prácticos a través de los métodos altamente accesibles del budismo kadampa moderno que él mismo presentó.",
                    'image' => 'maestros/fundador-gueshe-kelsang-gyatso.jpg',
                    'image_side' => 'left',
                ],
            ],
            [
                'type' => 'person',
                'key' => 'directora',
                'content' => [
                    'role' => 'Directora',
                    'name' => 'Guenla Dekyong',
                    'subtitle' => 'Directora espiritual general',
                    'body' => "Ha sido estudiante del venerable Gueshela durante más de treinta años, entrenándose bajo su guía en todos los aspectos del Dharma Kadam.\n\nGuenla Dekyong enseña en festivales kadampa por todo el mundo y es maestra residente del Centro de Meditación Kadampa Manjushri en Reino Unido.",
                    'image' => 'maestros/directora-guenla-dekyong.jpg',
                    'image_side' => 'right',
                ],
            ],
            [
                'type' => 'person',
                'key' => 'subdirector',
                'content' => [
                    'role' => 'Subdirector',
                    'name' => 'Guenla Jampa',
                    'subtitle' => 'Subdirector espiritual general',
                    'body' => "Es muy admirado por su afectuosa personalidad y sus claras e inspiradoras enseñanzas.\n\nGuenla Jampa enseña en festivales kadampa por todo el mundo y es maestro residente del Centro Internacional de Retiros de Gran Cañón, en Arizona, EEUU.",
                    'image' => 'maestros/subdirector-guenla-jampa.jpg',
                    'image_side' => 'left',
                ],
            ],
            [
                'type' => 'person',
                'key' => 'directora-argentina',
                'content' => [
                    'role' => 'Directora en Argentina',
                    'name' => 'Guen Kelsang Rinchung',
                    'subtitle' => 'Directora espiritual nacional en Argentina y Uruguay · Maestra residente del CMK Argentina',
                    'body' => "Desde el 2004 ha estudiado bajo la guía del venerable Gueshe Kelsang Gyatso Rimpoché.\n\nGuen Rinchung presenta las enseñanzas de Buda con un estilo amoroso y alegre, transmitiéndolas de manera práctica y muy sencilla. Con su ejemplo de amor y sabiduría, inspira a sus estudiantes a practicar el Dharma en sus vidas cotidianas, mostrándonos el camino que nos conduce hacia la felicidad verdadera.",
                    'image' => 'maestros/directora-argentina-guen-rinchung.jpg',
                    'image_side' => 'right',
                ],
            ],
            [
                'type' => 'text_image',
                'key' => 'maestro',
                'content' => [
                    'heading' => 'Kelsang Panchen',
                    'body' => "Maestro Principal de Rosario\n\nEs el segundo maestro residente del Centro de Meditación Kadampa Argentina. Encontró las enseñanzas de Buda a los 23 años y al poco tiempo se ordenó como monje kadampa. Realizó su formación como maestro en el ITTP (International Intensive Training Program) en el Templo de Manyushri (Inglaterra) y en el Centro de Meditación Kadampa Barcelona. Sus enseñanzas son cercanas a la gente y de gran calidez. Es reconocido por su serenidad y claridad al transmitir las enseñanzas de Buda. Kelsang Panchen, es un ejemplo muy inspirador.",
                    'image' => 'shared/K_Panchen.webp',
                    'image_side' => 'left',
                    'link_label' => null,
                    'link_url' => null,
                ],
            ],
            [
                'type' => 'faq',
                'key' => 'faq',
                'content' => [
                    'heading' => 'Preguntas frecuententes',
                    'faq_refs' => [0, 1, 2, 3, 4, 5],
                ],
            ],
        ],
    ],

    'voluntariado' => [
        'title' => 'Voluntariado',
        'menu_label' => 'Voluntariado',
        'menu_order' => 5,
        'meta_description' => 'Voluntariado Forma parte de la comunidad de Meditar en Rosario a través del programa de voluntariado Este espacio de paz y meditación depende completamente de',
        'sections' => [
            [
                'type' => 'page_header',
                'key' => 'titulo',
                'content' => [
                    'heading' => 'VOLUNTARIADO',
                    'intro' => null,
                    'style' => 'sky',
                ],
            ],
            [
                // El banner ancho de Zona Norte (1920x500) y no el afiche de un
                // evento: la portada acompaña a la página, no anuncia una fecha.
                'type' => 'hero',
                'key' => 'banner',
                'content' => [
                    'image' => 'abonos/banner-zona-norte.jpg',
                    'heading' => null,
                    'subheading' => null,
                ],
            ],
            [
                'type' => 'text_block',
                'key' => 'intro',
                'content' => [
                    'heading' => 'Voluntariado',
                    'body' => "Forma parte de la comunidad de Meditar en Rosario a través del programa de voluntariado\n\nEste espacio de paz y meditación depende completamente de voluntarios, desde aquellos que visitan el centro para ayudar de vez en cuando a nuestros managers y maestros dedicados.",
                    'links' => [],
                ],
            ],
            [
                'type' => 'gallery',
                'key' => 'galeria',
                'content' => [
                    'heading' => null,
                    'images' => [
                        'voluntariado/45.jpg',
                        'voluntariado/42.jpg',
                        'voluntariado/44.jpg',
                        'voluntariado/43.jpg',
                    ],
                ],
            ],
            [
                'type' => 'text_block',
                'key' => 'que-esperar',
                'content' => [
                    'heading' => '¿Qué esperar?',
                    'body' => "Las actividades con las que podes colaborar son muy variadas, desde recepción, preparación de la sala de meditación, limpieza y cambios de ofrendas a fotografía, video y difusión en redes sociales.\n\nSi tenés el deseo ayudar de alguna manera, ¡avisanos! No necesitas tener ninguna habilidad en especial ni comprometerse regularmente. ¡Cualquier ayuda es bienvenida!",
                    'links' => [],
                ],
            ],
            [
                'type' => 'text_block',
                'key' => 'que-ofrece',
                'content' => [
                    'heading' => '¿Qué te ofrece el voluntariado?',
                    'body' => "El voluntariado ofrece una experiencia única y enriquecedora, tanto a nivel personal como espiritual.\n\nEs una gran oportunidad de conocer de cerca una comunidad budista y de acumular buena energia ayudando a difundir el Dharma (las enseñanzas de Buda) y la meditación en un ambiente cálido y divertido.\n\nCompartirás tiempo con personas de todos los ámbitos, cada una con perspectivas y habilidades diferentes que pueden ayudarte a crecer de maneras inesperadas y significativas.",
                    'links' => [],
                ],
            ],
            [
                'type' => 'text_image',
                'key' => 'invitacion',
                'content' => [
                    'heading' => null,
                    'body' => "Muchas veces, entre el ritmo acelerado del día a día, sentimos que no tenemos tiempo para nada, y mucho menos para meditar. Sin embargo, dedicar apenas 30 minutos a la meditación puede marcar una gran diferencia. Incorporarla, nos ayuda a soltar tensiones y a reducir preocupaciones.\n\nSumate a nuestras meditaciones guiadas paso a paso por practicantes budistas y comenzá a disfrutar de los beneficios de la meditación. ¡Es fácil y accesible para todos!",
                    'image' => 'gratis/MEDITACIONES-GUIDAS-EN-30-MINUTOS-GRATIS-ROSARIO2.jpg',
                    'image_side' => 'left',
                    'link_label' => null,
                    'link_url' => null,
                ],
            ],
            [
                'type' => 'bullet_list',
                'key' => 'beneficios-meditacion',
                'content' => [
                    'heading' => 'Beneficios de la meditación:',
                    'intro' => null,
                    'items' => [
                        'Experimenta profunda relajación',
                        'Mejora tu salud física y mental',
                        'Reduce el estrés y la ansiedad',
                        'Suelta y vive más liger@',
                    ],
                ],
            ],
            [
                'type' => 'text_block',
                'key' => 'bienvenida',
                'content' => [
                    'heading' => '¡Todo el mundo es bienvenido!',
                    'body' => null,
                    'links' => [],
                ],
            ],
            [
                'type' => 'faq',
                'key' => 'faq',
                'content' => [
                    'heading' => 'Preguntas frecuententes',
                    'faq_refs' => [0, 1, 2, 3, 4, 5],
                ],
            ],
        ],
    ],

    'abonos' => [
        'title' => 'Abonos',
        'menu_label' => 'Abonos',
        'menu_order' => 6,
        'meta_description' => 'Abono mensual: acceso a todas las clases del mes y descuentos. Tres tarjetas: Clases, Corazón y Benefactor.',
        'sections' => [
            [
                'type' => 'hero',
                'key' => 'banner',
                'content' => [
                    'image' => 'abonos/banner-zona-norte.jpg',
                    'heading' => null,
                    'subheading' => null,
                ],
            ],
            [
                'type' => 'pricing',
                'key' => 'abono-mensual',
                'content' => [
                    'heading' => 'Abono Mensual',
                    'intro' => "Acceso a todas las clases del mes y descuentos.\n\nHay tres tarjetas con diferentes opciones de descuentos:",
                    'plans' => [
                        [
                            'name' => 'Tarjeta Clases',
                            'price' => '$45.000',
                            'period' => '/mes',
                            'features' => "Pase libre a todas las clases semanales de lunes y miércoles\nDescuentos en los cursos de sábados",
                            'note' => 'Sin permanencia mínima',
                            'highlighted' => false,
                        ],
                        [
                            'name' => 'Tarjeta Corazón',
                            'price' => '$55.000',
                            'period' => '/mes',
                            'features' => "Pase libre a todas las clases semanales\nCursos de sábados de manera gratuita\nDescuentos en retiros y eventos especiales",
                            'note' => 'Permanencia mínima 3 meses',
                            'highlighted' => true,
                        ],
                        [
                            'name' => 'Tarjeta Benefactor',
                            'price' => '$70.000',
                            'period' => '/mes',
                            'features' => "Pase libre a todas las clases semanales\nCursos de sábados de manera gratuita\nMayores descuentos en retiros y eventos especiales",
                            'note' => 'Permanencia mínima 3 meses',
                            'highlighted' => false,
                        ],
                    ],
                    'footnote' => 'Con cualquiera de estas tarjetas estás contribuyendo al desarrollo del Centro de Budismo Kadampa Nagaryhuna. ¡Todos son bienvenidos!',
                    'cta_label' => 'Inscribite al Abono Mensual',
                    'cta_url' => 'https://wa.me/5491166633921',
                ],
            ],
            [
                'type' => 'figure',
                'key' => 'que-incluye',
                'content' => [
                    'heading' => '¿Qué incluye?',
                    'image' => 'abonos/que-incluye.jpg',
                    'caption' => null,
                ],
            ],
        ],
    ],

    'programa-fundamental' => [
        'title' => 'Programa Fundamental',
        'menu_label' => 'Programa Fundamental',
        'menu_order' => 7,
        'meta_description' => 'Programa Fundamental (PF): estudio en profundidad del budismo mahayana, sus beneficios y el libro que estamos estudiando, Compasión Universal.',
        'sections' => [
            [
                'type' => 'hero',
                'key' => 'banner',
                'content' => [
                    'image' => 'programa-fundamental/encabezado.jpg',
                    'heading' => null,
                    'subheading' => null,
                ],
            ],
            [
                'type' => 'page_header',
                'key' => 'titulo',
                'content' => [
                    'heading' => 'PROGRAMA FUNDAMENTAL',
                    'intro' => 'El objetivo del Programa Fundamental (PF) es ofrecer una presentación sistemática de temas específicos del budismo mahayana para que los practicantes profundicen en su conocimiento y experiencia del budismo. El programa incluye 5 textos.',
                    'style' => 'orange',
                ],
            ],
            [
                'type' => 'bullet_list',
                'key' => 'beneficios',
                'content' => [
                    'heading' => 'Beneficios del Programa Fundamental',
                    'intro' => 'El estudio de estas obras nos aporta numerosos beneficios, que resumimos a continuación:',
                    'items' => [
                        'Profundizás tu conocimiento y experiencia del budismo mahayana.',
                        'Seguís un método estructurado que combina lectura, contemplación, meditación y debate.',
                        'Desarrollás una comprensión más profunda de las enseñanzas de Buda.',
                        'Aplicás las enseñanzas de manera práctica en tu vida cotidiana.',
                    ],
                ],
            ],
            [
                'type' => 'text_block',
                'key' => 'libro-titulo',
                'content' => [
                    'heading' => 'Libro que estamos estudiando',
                    'body' => null,
                    'image' => null,
                    'image_side' => 'right',
                    'links' => [],
                ],
            ],
            [
                'type' => 'text_image',
                'key' => 'libro',
                'content' => [
                    'heading' => 'Compasión universal',
                    'body' => "Inspiración para tiempos difíciles — del venerable Gueshe Kelsang Gyatso Rimpoché.\n\nEste texto profundo y accesible nos guía paso a paso en el desarrollo de la compasión y la sabiduría que necesitamos para enfrentar los desafíos de la vida con una mente más flexible y amorosa.",
                    'image' => 'programa-fundamental/compasion-universal.png',
                    'image_side' => 'left',
                    'link_label' => 'Inscribite al Programa Fundamental',
                    'link_url' => 'https://wa.me/5491166633921',
                ],
            ],
        ],
    ],

    // Clon de 'eventos-especiales': mismas secciones, mismo orden y mismo copy.
    // Solo cambian el título de la página y el encabezado del page_header.
    'cursos-y-retiros' => [
        'title' => 'Cursos y Retiros',
        'menu_label' => 'Cursos y Retiros',
        'menu_order' => 8,
        'meta_description' => 'Cursos y retiros de meditación kadampa: encuentros especiales para profundizar en la práctica, con enseñanzas y meditaciones guiadas.',
        'sections' => [
            [
                'type' => 'page_header',
                'key' => 'titulo',
                'content' => [
                    'heading' => 'CURSOS Y RETIROS',
                    'intro' => null,
                    'style' => 'sky',
                ],
            ],
            [
                'type' => 'hero',
                'key' => 'banner',
                'content' => [
                    'image' => 'eventos-especiales/vista-julio-rosario-kelsang-panchen7.jpg',
                    'heading' => null,
                    'subheading' => null,
                ],
            ],
            [
                // Plantilla de ficha, con la misma estructura que una clase. La
                // siembra CursosYRetirosFichaSeeder y entra oculta: el contenido
                // es de relleno y lo completa el dueño antes de publicarla.
                'type' => 'class_info',
                'key' => 'curso',
                'content' => [
                    'heading' => "Nombre del curso o retiro\nCon quién lo dicta",
                    'body' => "Contá de qué se trata: a quién está dirigido, qué se practica y con qué se va quien participa.\n\n*No hace falta experiencia previa ni inscripción.\n\n¡TODO EL MUNDO ES BIENVENIDO!",
                    'schedule' => 'Día y horario',
                    // Vacías a propósito: una fecha de ejemplo terminaría publicada
                    // en el calendario si se muestra la ficha sin completarla.
                    'occurrences' => [],
                    'location' => 'Dirección donde se dicta',
                    'price' => 'Precio o bono',
                    'cta_label' => 'INSCRIPCIÓN',
                    'cta_url' => 'https://meditarenargentina.org/tarjeta-kadampa/',
                    'image' => null,
                ],
            ],
            [
                'type' => 'event_list',
                'key' => 'eventos',
                'content' => [
                    'heading' => null,
                    'empty_text' => 'próximamente',
                ],
            ],
            [
                'type' => 'text_block',
                'key' => 'bienvenida',
                'content' => [
                    'heading' => '¡Todo el mundo es bienvenido!',
                    'body' => null,
                    'links' => [],
                ],
            ],
            [
                'type' => 'text_image',
                'key' => 'maestro',
                'content' => [
                    'heading' => 'Kelsang Panchen',
                    'body' => "Maestro Principal de Rosario\n\nEs el segundo maestro residente del Centro de Meditación Kadampa Argentina. Encontró las enseñanzas de Buda a los 23 años y al poco tiempo se ordenó como monje kadampa. Realizó su formación como maestro en el ITTP (International Intensive Training Program) en el Templo de Manyushri (Inglaterra) y en el Centro de Meditación Kadampa Barcelona. Sus enseñanzas son cercanas a la gente y de gran calidez. Es reconocido por su serenidad y claridad al transmitir las enseñanzas de Buda. Kelsang Panchen, es un ejemplo muy inspirador.",
                    'image' => 'shared/K_Panchen.webp',
                    'image_side' => 'right',
                    'link_label' => null,
                    'link_url' => null,
                ],
            ],
            [
                'type' => 'card_grid',
                'key' => 'festivales',
                'content' => [
                    'heading' => 'NACIONALES E INTERNACIONALES',
                    'cards' => [
                        [
                            'image' => 'shared/Festival-internacional-de-Verano-UK.jpg',
                            'title' => null,
                            'text' => null,
                            'url' => 'https://kadampafestivals.org/es/verano',
                        ],
                        [
                            'image' => 'shared/Festival-internacional-de-otono.jpg',
                            'title' => null,
                            'text' => null,
                            'url' => 'https://kadampafestivals.org/es/otono',
                        ],
                        [
                            'image' => 'shared/Festival-del-Dharma-buenos-aires-2026.jpg',
                            'title' => null,
                            'text' => null,
                            'url' => 'https://meditarenargentina.org/eventos-nacionales/',
                        ],
                    ],
                ],
            ],
            [
                'type' => 'map',
                'key' => 'donde-estamos',
                'content' => [
                    'heading' => '¿Dónde estamos?',
                    'query' => 'Meditar en Rosario Cajaraville 173, S2000 Rosario, Santa Fe',
                ],
            ],
            [
                'type' => 'bullet_list',
                'key' => 'detalles-clase',
                'content' => [
                    'heading' => 'Detalles de las clases',
                    'intro' => '¿Qué temas se tratan en las sesiones?',
                    'items' => [
                        'Técnicas para el aprendizaje de la meditación y profundización en tu experiencia.',
                        'Meditaciones guiadas en todas las sesiones.',
                        'Enseñanzas prácticas sobre cómo aplicar la antigua sabiduría de Buda en nuestra ajetreada vida moderna.',
                        'Aunque el aprendizaje es progresivo, podés sumarte en cualquier momento.',
                    ],
                ],
            ],
            [
                'type' => 'text_block',
                'key' => 'estructura-clase',
                'content' => [
                    'heading' => 'Estructura de una sesión',
                    'body' => "¿Qué esperar de una clase?\n\nMeditación guiada\n\nCada sesión comienza con una meditación en la respiración, para calmar la mente, eliminar las distracciones y cultivar paz interior.\n\nEnseñanza\n\nA continuación, una explicación de los métodos prácticos que provienen de las enseñanzas de Buda para ser felices en nuestro día a día, solucionar nuestros problemas internos y cultivar estados mentales apacibles.\n\nMeditación final\n\nLas sesiones terminan con una segunda meditación basada en las ideas aprendidas y consejos para integrarlos en nuestra vida cotidiana.",
                    'links' => [],
                ],
            ],
            [
                'type' => 'faq',
                'key' => 'faq',
                'content' => [
                    'heading' => 'Preguntas frecuententes',
                    'faq_refs' => [0, 1, 2, 3, 4, 5],
                ],
            ],
        ],
    ],

    // Las actividades no se cargan acá: la grilla las junta de las fichas de clase
    // visibles (con sus "Fechas para el calendario") y de los eventos marcados en
    // el panel → Calendario.
    'calendario' => [
        'title' => 'Calendario',
        'menu_label' => 'Calendario',
        'menu_order' => 9,
        'meta_description' => 'Calendario mensual de las clases semanales, los cursos y retiros y las actividades gratuitas del centro.',
        'sections' => [
            [
                'type' => 'page_header',
                'key' => 'titulo',
                'content' => [
                    'heading' => 'CALENDARIO',
                    'intro' => null,
                    'style' => 'sky',
                ],
            ],
            [
                'type' => 'event_calendar',
                'key' => 'calendario',
                'content' => [
                    'heading' => null,
                    'intro' => 'Las clases semanales, los cursos y retiros y las actividades gratuitas, mes por mes.',
                    'empty_text' => 'este mes no tiene actividades cargadas',
                ],
            ],
        ],
    ],

];
