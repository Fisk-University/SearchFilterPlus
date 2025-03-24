<?php
namespace SearchFilterPlus;

return [
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'controllers' => [
        'factories' => [
            'SearchFilterPlus\Controller\Admin\Index' => Service\Controller\IndexControllerFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            'SearchFilterPlus\DateRangeHandler' => Service\DateRangeHandlerFactory::class,
        ],
    ],
    'form_elements' => [
        'factories' => [
            'SearchFilterPlus\Form\ConfigForm' => Service\Form\ConfigFormFactory::class,
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'date-range-filter' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/date-range-filter',
                            'defaults' => [
                                '__NAMESPACE__' => 'SearchFilterPlus\Controller\Admin',
                                'controller' => 'Index',
                                'action' => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                    ],
                ],
            ],
        ],
    ],
    'daterange_filter' => [
        'min_year' => 1910,
        'max_year' => 1950,
        'property' => 'dcterms:date',
    ],
    'js_translate_strings' => [
        'Date Range', 
        'Apply', 
        'Reset', 
        'to',
    ],
];