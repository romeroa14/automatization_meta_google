<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MetaCampaignVariablesService
{
    /**
     * Variables requeridas para crear una CAMPAÑA en Meta
     */
    public function getCampaignRequiredFields(): array
    {
        return [
            'name' => [
                'type' => 'string',
                'required' => true,
                'description' => 'Nombre de la campaña',
                'max_length' => 256,
                'example' => 'Campaña Verano 2025'
            ],
            'objective' => [
                'type' => 'enum',
                'required' => true,
                'description' => 'Objetivo de la campaña',
                'options' => [
                    'OUTCOME_TRAFFIC' => 'Tráfico al sitio web',
                    'OUTCOME_ENGAGEMENT' => 'Compromiso',
                    'OUTCOME_AWARENESS' => 'Conciencia de marca',
                    'OUTCOME_LEADS' => 'Generación de leads',
                    'OUTCOME_SALES' => 'Ventas',
                    'OUTCOME_APP_PROMOTION' => 'Promoción de app',
                    'MESSAGES' => 'Mensajes',
                    'CONVERSIONS' => 'Conversiones',
                    'TRAFFIC' => 'Tráfico',
                    'REACH' => 'Alcance',
                    'BRAND_AWARENESS' => 'Conciencia de marca',
                    'ENGAGEMENT' => 'Compromiso',
                    'LEAD_GENERATION' => 'Generación de leads',
                    'SALES' => 'Ventas',
                    'APP_INSTALLS' => 'Instalaciones de app',
                    'VIDEO_VIEWS' => 'Visualizaciones de video'
                ]
            ],
            'status' => [
                'type' => 'enum',
                'required' => true,
                'description' => 'Estado de la campaña',
                'options' => [
                    'ACTIVE' => 'Activa',
                    'PAUSED' => 'Pausada',
                    'DELETED' => 'Eliminada',
                    'ARCHIVED' => 'Archivada'
                ],
                'default' => 'PAUSED'
            ],
            'special_ad_categories' => [
                'type' => 'array',
                'required' => false,
                'description' => 'Categorías especiales de anuncios',
                'options' => [
                    'CREDIT' => 'Crédito',
                    'EMPLOYMENT' => 'Empleo',
                    'HOUSING' => 'Vivienda',
                    'POLITICAL_ELECTORAL' => 'Político electoral',
                    'SOCIAL_ISSUES_ELECTIONS' => 'Problemas sociales/elecciones'
                ]
            ]
        ];
    }

    /**
     * Variables requeridas para crear un CONJUNTO DE ANUNCIOS (AdSet) en Meta
     */
    public function getAdSetRequiredFields(): array
    {
        return [
            'name' => [
                'type' => 'string',
                'required' => true,
                'description' => 'Nombre del conjunto de anuncios',
                'max_length' => 256,
                'example' => 'Conjunto Principal - Mujeres 25-45'
            ],
            'campaign_id' => [
                'type' => 'string',
                'required' => true,
                'description' => 'ID de la campaña padre',
                'format' => 'numeric'
            ],
            'optimization_goal' => [
                'type' => 'enum',
                'required' => true,
                'description' => 'Objetivo de optimización',
                'options' => [
                    'IMPRESSIONS' => 'Impresiones',
                    'REACH' => 'Alcance',
                    'CLICKS' => 'Clics',
                    'ENGAGED_USERS' => 'Usuarios comprometidos',
                    'CONVERSIONS' => 'Conversiones',
                    'LANDING_PAGE_VIEWS' => 'Vistas de página de destino',
                    'LINK_CLICKS' => 'Clics en enlaces',
                    'POST_ENGAGEMENT' => 'Compromiso con publicaciones',
                    'VIDEO_VIEWS' => 'Visualizaciones de video',
                    'LEAD_GENERATION' => 'Generación de leads',
                    'MESSAGES' => 'Mensajes',
                    'APP_INSTALLS' => 'Instalaciones de app',
                    'VALUE' => 'Valor',
                    'THRUPLAY' => 'Reproducción completa'
                ]
            ],
            'billing_event' => [
                'type' => 'enum',
                'required' => true,
                'description' => 'Evento de facturación',
                'options' => [
                    'IMPRESSIONS' => 'Impresiones',
                    'CLICKS' => 'Clics',
                    'CONVERSIONS' => 'Conversiones',
                    'LINK_CLICKS' => 'Clics en enlaces',
                    'POST_ENGAGEMENT' => 'Compromiso con publicaciones',
                    'VIDEO_VIEWS' => 'Visualizaciones de video',
                    'LEAD_GENERATION' => 'Generación de leads',
                    'MESSAGES' => 'Mensajes',
                    'APP_INSTALLS' => 'Instalaciones de app',
                    'THRUPLAY' => 'Reproducción completa'
                ]
            ],
            'bid_amount' => [
                'type' => 'integer',
                'required' => false,
                'description' => 'Cantidad de oferta (en centavos)',
                'min' => 1,
                'example' => 100 // $1.00
            ],
            'daily_budget' => [
                'type' => 'integer',
                'required' => false,
                'description' => 'Presupuesto diario (en centavos)',
                'min' => 100, // $1.00
                'example' => 1000 // $10.00
            ],
            'lifetime_budget' => [
                'type' => 'integer',
                'required' => false,
                'description' => 'Presupuesto de por vida (en centavos)',
                'min' => 100,
                'example' => 10000 // $100.00
            ],
            'start_time' => [
                'type' => 'datetime',
                'required' => false,
                'description' => 'Hora de inicio de la campaña',
                'format' => 'ISO 8601',
                'example' => '2025-09-20T00:00:00-0400'
            ],
            'end_time' => [
                'type' => 'datetime',
                'required' => false,
                'description' => 'Hora de finalización de la campaña',
                'format' => 'ISO 8601',
                'example' => '2025-09-30T23:59:59-0400'
            ],
            'targeting' => [
                'type' => 'object',
                'required' => true,
                'description' => 'Configuración de segmentación',
                'fields' => $this->getTargetingFields()
            ],
            'promoted_object' => [
                'type' => 'object',
                'required' => false,
                'description' => 'Objeto promocionado',
                'fields' => $this->getPromotedObjectFields()
            ]
        ];
    }

    /**
     * Variables requeridas para crear un ANUNCIO (Ad) en Meta
     */
    public function getAdRequiredFields(): array
    {
        return [
            'name' => [
                'type' => 'string',
                'required' => true,
                'description' => 'Nombre del anuncio',
                'max_length' => 256,
                'example' => 'Anuncio Principal - Oferta Verano'
            ],
            'adset_id' => [
                'type' => 'string',
                'required' => true,
                'description' => 'ID del conjunto de anuncios padre',
                'format' => 'numeric'
            ],
            'creative' => [
                'type' => 'object',
                'required' => true,
                'description' => 'Configuración creativa del anuncio',
                'fields' => $this->getCreativeFields()
            ],
            'status' => [
                'type' => 'enum',
                'required' => true,
                'description' => 'Estado del anuncio',
                'options' => [
                    'ACTIVE' => 'Activo',
                    'PAUSED' => 'Pausado',
                    'DELETED' => 'Eliminado',
                    'ARCHIVED' => 'Archivado'
                ],
                'default' => 'PAUSED'
            ],
            'tracking_specs' => [
                'type' => 'array',
                'required' => false,
                'description' => 'Especificaciones de seguimiento',
                'example' => [
                    [
                        'action_type' => 'offsite_conversion',
                        'fb_pixel' => '123456789012345'
                    ]
                ]
            ]
        ];
    }

    /**
     * Campos de segmentación (Targeting)
     */
    private function getTargetingFields(): array
    {
        return [
            'geo_locations' => [
                'type' => 'object',
                'description' => 'Ubicaciones geográficas',
                'fields' => [
                    'countries' => [
                        'type' => 'array',
                        'description' => 'Códigos de país ISO 3166-1 alpha-2',
                        'example' => ['US', 'CA', 'MX']
                    ],
                    'regions' => [
                        'type' => 'array',
                        'description' => 'Regiones/estados',
                        'example' => [
                            ['key' => 'US_CA', 'name' => 'California']
                        ]
                    ],
                    'cities' => [
                        'type' => 'array',
                        'description' => 'Ciudades',
                        'example' => [
                            ['key' => 'US_CA_LOS_ANGELES', 'name' => 'Los Angeles']
                        ]
                    ]
                ]
            ],
            'age_min' => [
                'type' => 'integer',
                'description' => 'Edad mínima',
                'min' => 13,
                'max' => 65,
                'default' => 18
            ],
            'age_max' => [
                'type' => 'integer',
                'description' => 'Edad máxima',
                'min' => 13,
                'max' => 65,
                'default' => 65
            ],
            'genders' => [
                'type' => 'array',
                'description' => 'Géneros',
                'options' => [1, 2], // 1 = Mujeres, 2 = Hombres
                'example' => [1, 2] // Ambos géneros
            ],
            'interests' => [
                'type' => 'array',
                'description' => 'Intereses',
                'example' => [
                    ['id' => '6003107902433', 'name' => 'Fashion'],
                    ['id' => '6004037226511', 'name' => 'Beauty']
                ]
            ],
            'behaviors' => [
                'type' => 'array',
                'description' => 'Comportamientos',
                'example' => [
                    ['id' => '6002714895372', 'name' => 'Online shoppers']
                ]
            ],
            'custom_audiences' => [
                'type' => 'array',
                'description' => 'Audiencias personalizadas',
                'example' => [
                    ['id' => '123456789012345', 'name' => 'Mi Audiencia Personalizada']
                ]
            ],
            'lookalike_audiences' => [
                'type' => 'array',
                'description' => 'Audiencias similares',
                'example' => [
                    [
                        'id' => '123456789012345',
                        'name' => 'Audiencia Similar 1%',
                        'ratio' => 0.01
                    ]
                ]
            ]
        ];
    }

    /**
     * Campos de objeto promocionado
     */
    private function getPromotedObjectFields(): array
    {
        return [
            'object_store_url' => [
                'type' => 'string',
                'description' => 'URL de la tienda',
                'example' => 'https://example.com'
            ],
            'application_id' => [
                'type' => 'string',
                'description' => 'ID de la aplicación',
                'example' => '123456789012345'
            ],
            'page_id' => [
                'type' => 'string',
                'description' => 'ID de la página de Facebook',
                'example' => '123456789012345'
            ],
            'pixel_id' => [
                'type' => 'string',
                'description' => 'ID del pixel de Facebook',
                'example' => '123456789012345'
            ],
            'custom_event_type' => [
                'type' => 'enum',
                'description' => 'Tipo de evento personalizado',
                'options' => [
                    'COMPLETE_REGISTRATION' => 'Registro completo',
                    'CONTENT_VIEW' => 'Vista de contenido',
                    'SEARCH' => 'Búsqueda',
                    'RATE' => 'Calificación',
                    'TUTORIAL_COMPLETION' => 'Tutorial completado',
                    'ADD_TO_CART' => 'Agregar al carrito',
                    'ADD_TO_WISHLIST' => 'Agregar a lista de deseos',
                    'INITIATED_CHECKOUT' => 'Iniciar checkout',
                    'ADD_PAYMENT_INFO' => 'Agregar información de pago',
                    'PURCHASE' => 'Compra',
                    'LEAD' => 'Lead',
                    'COMPLETE_BOOKING' => 'Reserva completa'
                ]
            ]
        ];
    }

    /**
     * Campos creativos
     */
    private function getCreativeFields(): array
    {
        return [
            'object_story_spec' => [
                'type' => 'object',
                'description' => 'Especificación de historia de objeto',
                'fields' => [
                    'page_id' => [
                        'type' => 'string',
                        'required' => true,
                        'description' => 'ID de la página de Facebook'
                    ],
                    'link_data' => [
                        'type' => 'object',
                        'description' => 'Datos del enlace',
                        'fields' => [
                            'message' => [
                                'type' => 'string',
                                'description' => 'Mensaje del anuncio',
                                'max_length' => 2000
                            ],
                            'link' => [
                                'type' => 'string',
                                'description' => 'URL del enlace',
                                'format' => 'url'
                            ],
                            'name' => [
                                'type' => 'string',
                                'description' => 'Título del enlace',
                                'max_length' => 100
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'Descripción del enlace',
                                'max_length' => 200
                            ],
                            'picture' => [
                                'type' => 'string',
                                'description' => 'URL de la imagen',
                                'format' => 'url'
                            ],
                            'call_to_action' => [
                                'type' => 'object',
                                'description' => 'Llamada a la acción',
                                'fields' => [
                                    'type' => [
                                        'type' => 'enum',
                                        'options' => [
                                            'SHOP_NOW' => 'Comprar ahora',
                                            'LEARN_MORE' => 'Aprender más',
                                            'SIGN_UP' => 'Registrarse',
                                            'DOWNLOAD' => 'Descargar',
                                            'BOOK_TRAVEL' => 'Reservar viaje',
                                            'GET_QUOTE' => 'Obtener cotización',
                                            'CONTACT_US' => 'Contáctanos',
                                            'DONATE' => 'Donar',
                                            'APPLY_NOW' => 'Aplicar ahora',
                                            'USE_APP' => 'Usar app',
                                            'INSTALL_APP' => 'Instalar app',
                                            'PLAY_GAME' => 'Jugar',
                                            'LISTEN_MUSIC' => 'Escuchar música',
                                            'WATCH_VIDEO' => 'Ver video',
                                            'GET_OFFER' => 'Obtener oferta',
                                            'REQUEST_TIME' => 'Solicitar tiempo',
                                            'SEE_MORE' => 'Ver más',
                                            'SUBSCRIBE' => 'Suscribirse',
                                            'INSTALL_MOBILE_APP' => 'Instalar app móvil'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'object_story_id' => [
                'type' => 'string',
                'description' => 'ID de la historia existente',
                'example' => '123456789012345_123456789012345'
            ],
            'image_hash' => [
                'type' => 'string',
                'description' => 'Hash de la imagen subida',
                'example' => 'abc123def456'
            ],
            'video_id' => [
                'type' => 'string',
                'description' => 'ID del video subido',
                'example' => '123456789012345'
            ],
            'title' => [
                'type' => 'string',
                'description' => 'Título del anuncio',
                'max_length' => 25
            ],
            'body' => [
                'type' => 'string',
                'description' => 'Cuerpo del anuncio',
                'max_length' => 90
            ]
        ];
    }

    /**
     * Obtener todas las variables en un formato estructurado
     */
    public function getAllVariables(): array
    {
        return [
            'campaign' => $this->getCampaignRequiredFields(),
            'adset' => $this->getAdSetRequiredFields(),
            'ad' => $this->getAdRequiredFields()
        ];
    }

    /**
     * Validar datos de campaña
     */
    public function validateCampaignData(array $data): array
    {
        $errors = [];
        $requiredFields = $this->getCampaignRequiredFields();

        foreach ($requiredFields as $field => $config) {
            if ($config['required'] && !isset($data[$field])) {
                $errors[] = "Campo requerido faltante: {$field}";
            }
        }

        return $errors;
    }

    /**
     * Validar datos de conjunto de anuncios
     */
    public function validateAdSetData(array $data): array
    {
        $errors = [];
        $requiredFields = $this->getAdSetRequiredFields();

        foreach ($requiredFields as $field => $config) {
            if ($config['required'] && !isset($data[$field])) {
                $errors[] = "Campo requerido faltante: {$field}";
            }
        }

        return $errors;
    }

    /**
     * Validar datos de anuncio
     */
    public function validateAdData(array $data): array
    {
        $errors = [];
        $requiredFields = $this->getAdRequiredFields();

        foreach ($requiredFields as $field => $config) {
            if ($config['required'] && !isset($data[$field])) {
                $errors[] = "Campo requerido faltante: {$field}";
            }
        }

        return $errors;
    }
}
