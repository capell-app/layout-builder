<?php

declare(strict_types=1);

return [
    'archive' => [
        'url' => 'https://capell.app/demo.zip',
        'checksum' => 'cf39f86a46f45bc9246352472dbbc39f70d79ee52de06e3c04f51b58fb436957',
        'max_bytes' => 52428800,
    ],

    'languages' => [
        'en' => [
            'name' => 'English',
            'locale' => 'en_GB',
            'code' => 'en',
            'flag' => 'gb-eng',
            'color' => '#f0f0f0',
            'default' => true,
        ],
        'fr' => [
            'name' => 'French',
            'locale' => 'fr_FR',
            'code' => 'fr',
            'flag' => 'fr',
            'color' => '#0072bb',
        ],
        'it' => [
            'name' => 'Italian',
            'locale' => 'it_IT',
            'code' => 'it',
            'flag' => 'it',
            'color' => '#008c45',
        ],
        'de' => [
            'name' => 'German',
            'locale' => 'de_DE',
            'code' => 'de',
            'flag' => 'de',
            'color' => '#4d4a4a',
        ],
        'es' => [
            'name' => 'Spanish',
            'locale' => 'es_ES',
            'code' => 'es',
            'flag' => 'es',
            'color' => '#f6b511',
        ],
    ],
    'countries' => [
        [
            'name' => 'United Kingdom',
            'key' => 'united-kingdom',
            'code' => 'GB',
            'iso' => 'GBR',
            'default' => true,
            'order' => 1,
        ], [
            'name' => 'France',
            'key' => 'france',
            'code' => 'FR',
            'iso' => 'FRA',
            'order' => 2,
        ], [
            'name' => 'Italy',
            'key' => 'italy',
            'code' => 'IT',
            'iso' => 'ITA',
            'order' => 3,
        ], [
            'name' => 'Germany',
            'key' => 'germany',
            'code' => 'DE',
            'iso' => 'DEU',
            'order' => 4,
        ], [
            'name' => 'Spain',
            'key' => 'spain',
            'code' => 'ES',
            'iso' => 'ESP',
            'order' => 5,
        ], [
            'name' => 'United States',
            'key' => 'united-states',
            'code' => 'US',
            'iso' => 'USA',
            'order' => 6,
        ],
    ],
    'contents' => [
        'en' => "<p><b>Lorem Ipsum</b> is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</p>",
        'fr' => '<p><b>Le Lorem Ipsum</b> est simplement du faux texte employé dans la composition et la mise en page avant impression. Le Lorem Ipsum est le faux texte standard de l\'imprimerie depuis les années 1500, quand un imprimeur anonyme assembla ensemble des morceaux de texte pour réaliser un livre spécimen de polices de texte. Il n\'a pas fait que survivre cinq siècles, mais s\'est aussi adapté à la bureautique informatique, sans que son contenu n\'en soit modifié. Il a été popularisé dans les années 1960 grâce à la vente de feuilles Letraset contenant des passages du Lorem Ipsum, et, plus récemment, par son inclusion dans des applications de mise en page de texte, comme Aldus PageMaker.</p>',
        'de' => '<p><b>Lorem Ipsum</b> ist ein einfacher Demo-Text für die Print- und Schriftindustrie. Lorem Ipsum ist in der Industrie bereits der Standard Demo-Text seit 1500, als ein unbekannter Schriftsteller eine Hand voll Wörter nahm und diese durcheinander warf um ein Musterbuch zu erstellen. Es hat nicht nur 5 Jahrhunderte überlebt, sondern auch in Spruch in die elektronische Schriftbearbeitung geschafft (bemerke, nahezu unverändert). Bekannt wurde es 1960, mit dem erscheinen von "Letraset", welches Passagen von Lorem Ipsum enhielt, so wie Desktop Software wie "Aldus PageMaker" - ebenfalls mit Lorem Ipsum.</p>',
        'it' => '<p><b>Lorem Ipsum</b> è un testo segnaposto utilizzato nel settore della tipografia e della stampa. Lorem Ipsum è considerato il testo segnaposto standard sin dal sedicesimo secolo, quando un anonimo tipografo prese una cassetta di caratteri e li assemblò per preparare un testo campione. È sopravvissuto non solo a più di cinque secoli, ma anche al passaggio alla videoimpaginazione, pervenendoci sostanzialmente inalterato. Fu reso popolare, negli anni ’60, con la diffusione dei fogli di caratteri trasferibili “Letraset”, che contenevano passaggi del Lorem Ipsum, e più recentemente da software di impaginazione come Aldus PageMaker, che includeva versioni del Lorem Ipsum.</p>',
        'es' => '<p><b>Lorem Ipsum</b> es simplemente el texto de relleno de las imprentas y archivos de texto. Lorem Ipsum ha sido el texto de relleno estándar de las industrias desde el año 1500, cuando un impresor (N. del T. persona que se dedica a la imprenta) desconocido usó una galería de textos y los mezcló de tal manera que logró hacer un libro de textos especimen. No sólo sobrevivió 500 años, sino que tambien ingresó como texto de relleno en documentos electrónicos, quedando esencialmente igual al original. Fue popularizado en los 60s con la creación de las hojas "Letraset", las cuales contenian pasajes de Lorem Ipsum, y más recientemente con software de autoedición, como por ejemplo Aldus PageMaker, el cual incluye versiones de Lorem Ipsum.</p>',
    ],
    'pages' => [
        // Mammals
        [
            'name' => [
                'en' => 'Mammals',
                'fr' => 'mammifères',
                'de' => 'Säugetiere',
                'it' => 'mammifere',
                'es' => 'mamíferos',
            ],
            'children' => [
                [
                    'name' => [
                        'en' => 'dogs',
                        'fr' => 'chiennes',
                        'de' => 'Hunde',
                        'it' => 'cagne',
                        'es' => 'perras',
                    ],
                    'children' => [
                        [
                            'name' => [
                                'en' => 'German Shepherd',
                                'fr' => 'Berger allemand',
                                'de' => 'Deutscher Schäferhund',
                                'it' => 'Pastore tedesco',
                                'es' => 'Pastor alemana',
                            ],
                            'children' => [
                                [
                                    'name' => [
                                        'en' => 'Show German Shepherd',
                                        'fr' => 'Berger allemand de spectacle',
                                        'de' => 'Show Deutscher Schäferhund',
                                        'it' => 'Mostra Pastore tedesco',
                                        'es' => 'Pastor alemán de exposición',
                                    ],
                                    'children' => [
                                        [
                                            'name' => [
                                                'en' => 'Black and Tan German Shepherd',
                                                'fr' => 'Berger allemand noir et feu',
                                                'de' => 'Schwarz und Tan Deutscher Schäferhund',
                                                'it' => 'Pastore tedesco nero e focato',
                                                'es' => 'Pastor alemán',
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    'name' => [
                                        'en' => 'Working German Shepherd',
                                        'fr' => 'Berger allemand de travail',
                                        'de' => 'Arbeitender Deutscher Schäferhund',
                                        'it' => 'Pastore tedesco da lavoro',
                                        'es' => 'Pastor alemán de trabajo',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => [
                                'en' => 'Spaniel',
                                'fr' => 'Épagneul',
                                'de' => 'Spaniel',
                                'it' => 'Spaniel',
                                'es' => 'Spaniel',
                            ],
                            'children' => [
                                [
                                    'name' => [
                                        'en' => 'Springer Spaniel',
                                        'fr' => 'Springer anglais',
                                        'de' => 'Springer Spaniel',
                                        'it' => 'Springer spaniel',
                                        'es' => 'Springer Spaniel',
                                    ],
                                    'children' => [
                                        [
                                            'name' => [
                                                'en' => 'English Springer Spaniel',
                                                'fr' => 'Épagneul springer anglais',
                                                'de' => 'Englischer Springer Spaniel',
                                                'it' => 'Springer spaniel inglese',
                                                'es' => 'Springer Spaniel Inglés',
                                            ],
                                            'children' => [
                                                [
                                                    'name' => [
                                                        'en' => 'Show English Springer Spaniel',
                                                        'fr' => 'Épagneul springer anglais de spectacle',
                                                        'de' => 'Show Englischer Springer Spaniel',
                                                        'it' => 'Mostra Springer Spaniel Inglese',
                                                        'es' => 'Springer Spaniel Inglés de Exposición',
                                                    ],
                                                    'children' => [
                                                        [
                                                            'name' => [
                                                                'en' => 'Black and White English Springer Spaniel',
                                                                'fr' => 'Épagneul springer anglais noir et blanc',
                                                                'de' => 'Schwarz-Weiß Englischer Springer Spaniel',
                                                                'it' => 'Springer Spaniel Inglese nero e bianco',
                                                                'es' => 'Springer Spaniel Inglés de Hígado y bianco',
                                                            ],
                                                        ],
                                                        [
                                                            'name' => [
                                                                'en' => 'Liver and White English Springer Spaniel',
                                                                'fr' => 'Épagneul springer anglais foie et blanc',
                                                                'de' => 'Leber- und Weißer Englischer Springer Spaniel',
                                                                'it' => 'Springer Spaniel Inglese fegato e bianco',
                                                                'es' => 'Springer Spaniel Inglés de Hígado y Blanco',
                                                            ],
                                                        ],
                                                    ],
                                                    [
                                                        'name' => [
                                                            'en' => 'Field English Springer Spaniel',
                                                            'fr' => 'Épagneul springer anglais de terrain',
                                                            'de' => 'Feld Englischer Springer Spaniel',
                                                            'it' => 'Springer Spaniel Inglese da campo',
                                                            'es' => 'Springer Spaniel Inglés de Campo',
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            'name' => [
                                                'en' => 'Welsh Springer Spaniel',
                                                'fr' => 'Épagneul springer gallois',
                                                'de' => 'Welsh Springer Spaniel',
                                                'it' => 'Springer Spaniel gallese',
                                                'es' => 'Springer Spaniel Galés',
                                            ],
                                        ],
                                        [
                                            'name' => [
                                                'en' => 'American Springer Spaniel',
                                                'fr' => 'Épagneul springer américain',
                                                'de' => 'Amerikanischer Springer Spaniel',
                                                'it' => 'Springer spaniel americano',
                                                'es' => 'Springer Spaniel Americano',
                                            ],
                                        ],
                                    ],
                                    [
                                        'name' => [
                                            'en' => 'English Cocker Spaniel',
                                            'fr' => 'Cocker anglais',
                                            'de' => 'Englischer Cocker Spaniel',
                                            'it' => 'Cocker Spaniel Inglese',
                                            'es' => 'Cocker Spaniel Inglés',
                                        ],
                                    ],
                                    [
                                        'name' => [
                                            'en' => 'Boykin Spaniel',
                                            'fr' => 'Épagneul Boykin',
                                            'de' => 'Boykin Spaniel',
                                            'it' => 'Boykin Spaniel',
                                            'es' => 'Perro de aguas boykin',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => [
                                'en' => 'Poodle',
                                'fr' => 'Caniche',
                                'de' => 'Pudel',
                                'it' => 'Barboncino',
                                'es' => 'Caniche',
                            ],
                        ],
                        [
                            'name' => [
                                'en' => 'Bulldog',
                                'fr' => 'Bouledogue',
                                'de' => 'Bulldogge',
                                'it' => 'Bulldog',
                                'es' => 'Buldog',
                            ],
                        ],
                        [
                            'name' => [
                                'en' => 'Siberian Husky',
                                'fr' => 'Husky sibérien',
                                'de' => 'Sibirischer Husky',
                                'it' => 'Husky siberiano',
                                'es' => 'Husky siberiano',
                            ],
                        ],
                        [
                            'name' => [
                                'en' => 'Golden Retriever',
                                'fr' => 'Golden retriever',
                                'de' => 'Golden Retriever',
                                'it' => 'Golden retriever',
                                'es' => 'perro perdiguero de oro',
                            ],
                        ],
                    ],
                ],
                [
                    'name' => [
                        'en' => 'cats',
                        'fr' => 'chattes',
                        'de' => 'Katzen',
                        'it' => 'gatte',
                        'es' => 'gatas',
                    ],
                    'children' => [
                        [
                            'name' => [
                                'en' => 'Siamese',
                                'fr' => 'Siamoise',
                                'de' => 'Siamese',
                                'it' => 'siamese',
                                'es' => 'Siamesa',
                            ],
                        ],
                        [
                            'name' => [
                                'en' => 'Burmese',
                                'fr' => 'chat birman',
                                'de' => 'birmanische Katze',
                                'it' => 'gatto birmano',
                                'es' => 'Birmana',
                            ],
                        ],
                        [
                            'name' => [
                                'en' => 'Sphynx',
                                'fr' => 'chat sphynx',
                                'de' => 'Sphynx-Katze',
                                'it' => 'gatto sfinge',
                                'es' => 'esfinge',
                            ],
                        ],
                    ],
                ],
                [
                    'name' => [
                        'en' => 'bears',
                        'fr' => 'ours',
                        'de' => 'Bären',
                        'it' => 'orsi',
                        'es' => 'osos',
                    ],
                    'children' => [
                        [
                            'name' => [
                                'en' => 'Grizzly Bear',
                                'fr' => 'Ours Grizzly',
                                'de' => 'Grizzlybär',
                                'it' => 'Orso grizzly',
                                'es' => 'Oso pardo',
                            ],
                        ],
                        [
                            'name' => [
                                'en' => 'Polar Bear',
                                'fr' => 'Ours polaire',
                                'de' => 'Eisbär',
                                'it' => 'Orso polare',
                                'es' => 'Oso polar',
                            ],
                        ],
                    ],
                ],
                [
                    'name' => [
                        'en' => 'elephants',
                        'fr' => 'éléphants',
                        'de' => 'Elefanten',
                        'it' => 'elefanti',
                        'es' => 'elefantes',
                    ],
                    'children' => [
                        [
                            'name' => [
                                'en' => 'African Elephant',
                                'fr' => 'Éléphant d\'Afrique',
                                'de' => 'Afrikanischer Elefant',
                                'it' => 'Elefante africano',
                                'es' => 'Elefante africano',
                            ],
                        ],
                        [
                            'name' => [
                                'en' => 'Asian Elephant',
                                'fr' => 'Éléphant d\'Asie',
                                'de' => 'Asiatischer Elefant',
                                'it' => 'Elefante asiatico',
                                'es' => 'Elefante asiático',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        // Birds
        [
            'name' => [
                'en' => 'Birds',
                'fr' => 'des oiseaux',
                'de' => 'Vögel',
                'it' => 'uccelli',
                'es' => 'aves',
            ],
            'children' => [
                [
                    'name' => [
                        'en' => 'falcon',
                        'fr' => 'faucons',
                        'de' => 'Falken',
                        'it' => 'falchi',
                        'es' => 'halcón',
                    ],
                ],
                [
                    'name' => [
                        'en' => 'eagles',
                        'fr' => 'aigles',
                        'de' => 'Adler',
                        'it' => 'Aquile',
                        'es' => 'águilas',
                    ],
                    'children' => [
                        [
                            'name' => [
                                'en' => 'Bald Eagle',
                                'fr' => 'Pygargue à tête blanche',
                                'de' => 'Weißkopfseeadler',
                                'it' => 'Aquila calva',
                                'es' => 'Águila calva',
                            ],
                        ],
                        [
                            'name' => [
                                'en' => 'Golden Eagle',
                                'fr' => 'Aigle en or',
                                'de' => 'Goldener Adler',
                                'it' => 'Aquila reale',
                                'es' => 'Águila dorada',
                            ],
                            'children' => [
                                [
                                    'name' => [
                                        'en' => 'Spanish Imperial Eagle',
                                        'fr' => 'Aigle impérial espagnol',
                                        'de' => 'Spanischer Kaiseradler',
                                        'it' => 'Aquila imperiale spagnola',
                                        'es' => 'Águila imperial ibérica',
                                    ],
                                ],
                                [
                                    'name' => [
                                        'en' => 'Martial Eagle',
                                        'fr' => 'Aigle martial',
                                        'de' => 'Martialadler',
                                        'it' => 'Aquila marziale',
                                        'es' => 'Águila marcial',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => [
                        'en' => 'owls',
                        'fr' => 'chouettes',
                        'de' => 'Eulen',
                        'it' => 'gufi',
                        'es' => 'búhos',
                    ],
                    'children' => [
                        [
                            'name' => [
                                'en' => 'Snowy Owl',
                                'fr' => 'Harfang des neiges',
                                'de' => 'Schneeeule',
                                'it' => 'Gufo delle nevi',
                                'es' => 'Buho Nevado',
                            ],
                        ],
                        [
                            'name' => [
                                'en' => 'Barn Owl',
                                'fr' => 'Effraie des clochers',
                                'de' => 'Schleiereule',
                                'it' => 'Barbagianni',
                                'es' => 'Lechuza',
                            ],
                        ],
                        [
                            'name' => [
                                'en' => 'Tawny Owl',
                                'fr' => 'Chouette hulotte',
                                'de' => 'Waldkauz',
                                'it' => 'Allocco',
                                'es' => 'Buho Carabo',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        // Fish
        [
            'name' => [
                'en' => 'Fish',
                'it' => 'pescare',
                'fr' => 'poisson',
                'de' => 'Fisch',
                'es' => 'pez',
            ],
            'children' => [
                [
                    'name' => [
                        'en' => 'Herring',
                        'fr' => 'hareng',
                        'de' => 'Hering',
                        'it' => 'aringa',
                        'es' => 'arenque',
                    ],
                ],
                [
                    'name' => [
                        'en' => 'Fresh Water',
                        'fr' => 'Acqua dolce',
                        'de' => 'Frisches Wasser',
                        'it' => 'Acqua dolce',
                        'es' => 'Agua dulce',
                    ],
                    'children' => [
                        [
                            'name' => [
                                'en' => 'Tench',
                                'fr' => 'Tanche',
                                'de' => 'Schleie',
                                'it' => 'Tinca',
                                'es' => 'Tenca',
                            ],
                        ],
                    ],
                ],
                [
                    'name' => [
                        'en' => 'Salt Water',
                        'fr' => 'Eau salée',
                        'de' => 'Salzwasser',
                        'it' => 'Acqua salata',
                        'es' => 'Agua salada',
                    ],
                    'children' => [
                        [
                            'name' => [
                                'en' => 'Yellow tang',
                                'fr' => 'Tang jaune',
                                'de' => 'Gelber Zapfen',
                                'it' => 'Codolo giallo',
                                'es' => 'Yellow tang',
                            ],
                        ],
                        [
                            'name' => [
                                'en' => 'Humpback anglerfish',
                                'fr' => 'baudroie à bosse',
                                'de' => 'Buckel-Seeteufel',
                                'it' => 'Rana pescatrice megattera',
                                'es' => 'rape jorobado',
                            ],
                        ],
                    ],
                ],
                [
                    'name' => [
                        'en' => 'sharks',
                        'fr' => 'les requins',
                        'de' => 'Haie',
                        'it' => 'squali',
                        'es' => 'Tiburón',
                    ],
                    'children' => [
                        [
                            'name' => [
                                'en' => 'Great White Shark',
                                'fr' => 'Grand requin blanc',
                                'de' => 'Großer weißer Hai',
                                'it' => 'Grande squalo bianco',
                                'es' => 'Gran tiburón blanco',
                            ],
                        ],
                        [
                            'name' => [
                                'en' => 'Hammerhead Shark',
                                'fr' => 'requin-marteau',
                                'de' => 'Hammerhai',
                                'it' => 'squalo martello',
                                'es' => 'Tiburon martillo',
                            ],
                        ],
                        [
                            'name' => [
                                'en' => 'Whale Shark',
                                'fr' => 'Requin baleine',
                                'de' => 'Walhai',
                                'it' => 'Squalo balena',
                                'es' => 'Tiburón ballena',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
