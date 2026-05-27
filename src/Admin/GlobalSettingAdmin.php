<?php

declare(strict_types=1);
/*
 * This file is part of the project by AGBOKOUDJO Franck.
 *
 * (c) AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * Phone: +229 01 67 25 18 86
 * LinkedIn: https://www.linkedin.com/in/internationales-web-apps-services-120520193/
 * Github: https://github.com/Agbokoudjo/
 * Company: INTERNATIONALES WEB APPS & SERVICES
 *
 * For more information, please feel free to contact the author.
 */

namespace App\Admin;

use App\Admin\WlindablaAdmin;
use App\Entity\GlobalSetting;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\CollectionType;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
#[AutoconfigureTag(
    name: 'sonata.admin',
    attributes: [
        'id'           => 'app.admin.globalSetting',
        'code'         => 'app.admin.globalSetting',
        'admin_code'   => 'app.admin.globalSetting',
        'model_class'  => GlobalSetting::class,
        'manager_type' => 'orm',
        'group'        => 'app.admin.group.configuration',
        'label'        => 'Paramètres Généraux',
        'pager_type'   => 'simple'
    ]
)]
final class GlobalSettingAdmin extends WlindablaAdmin
{
    public function __construct()
    {
        parent::__construct(
            "list__app.admin.global_setting",
            "show__app.admin.global_setting",
            "create__app.admin.global_setting",
            "edit__app.admin.global_setting"
        );
    }

    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'sonata_global_setting';
    }

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'global_setting';
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('export');
        $collection->remove('delete');
        $collection->remove('create');
    }
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            // Premier Onglet
            ->tab('Contacts & Adresses')
                ->with('Coordonnées Multiples', ['class' => 'col-md-12'])
                    ->add('emailContact', CollectionType::class, [
                        'label' => 'Adresses Email',
                        'allow_add' => true,
                        'allow_delete' => true,
                        'entry_type' => EmailType::class,
                        'by_reference' => false,
                        'auto_initialize' => false,
                        'entry_options' => [
                            'attr' => [
                                'placeholder' => 'ex: contact@protic.com', 
                                'class' => 'form-control',
                                'minlength' => 6,
                                'data-type' => "email",
                                'autocomplete'                      => 'email',
                                'maxlength'                         => 200,
                                'data-pattern'                      => '^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$',
                                'data-escapestrip-html-and-php-tags' => 'true',
                                'data-event-validate-blur'          => 'blur',
                                'data-event-validate-input'         => 'input',
                                'data-error-message-input'          => 'Veuillez saisir une adresse email valide.',
                                ]
                        ],
                    ])
                    ->add('phonePrimary', CollectionType::class, [
                        'label' => 'Numéros de Téléphone',
                        'allow_add' => true,
                        'allow_delete' => true,
                        'entry_type' => PhoneNumberType::class,
                        'by_reference' => false,
                        'auto_initialize' => false,
                        'entry_options' => [
                        'default_region' => 'BJ',
                        'format'      => \libphonenumber\PhoneNumberFormat::INTERNATIONAL,
                            'attr' => 
                            [
                                'placeholder' => 'ex: +229 95...',
                                'data-escapestrip-html-and-php-tags' => 'true',
                                'data-event-validate-blur'          => 'blur',
                                'minlength' => 8,
                                'maxlength' => 80,
                                'data-type'  => 'tel',
                                "data-eg-await" => '+229 XX XX XX XX',
                                'data-error-message-input'          => 'Numéro de téléphone invalide.',

                            ]
                        ],
                    ])
                    ->add('addresses', CollectionType::class, [
                        'label' => 'Adresses Physiques',
                        'allow_add' => true,
                        'allow_delete' => true,
                        'entry_type' => TextareaType::class,
                        'by_reference' => false,
                        'auto_initialize' => false,
                        'entry_options' => ['attr' => [
                            'class' => 'form-control', 'rows' => 2,
                            'data-pattern'=> '^[\p{L}\p{N}\p{M}\s\-\.\p{P}]$'
                            ]],
                    ])
                ->end() // Ferme le groupe 'Coordonnées Multiples'
            ->end() // Ferme l'onglet 'Contacts & Adresses'

            // Deuxième Onglet
            ->tab('Informations Juridiques')
                ->with('Données Légales', ['class' => 'col-md-12'])
                    ->add('legalRccm', TextType::class, [
                        'label' => 'Numéro RCCM',
                        'required' => false,
                        'property_path' => 'legalInfos[rccm]',
                        'attr' => ['placeholder' => 'RB/ABC/21 A 32987']
                    ])
                    ->add('legalIfu', TextType::class, [
                        'label' => 'Numéro IFU',
                        'required' => false,
                        'property_path' => 'legalInfos[ifu]',
                        'attr' => ['placeholder' => '0202112604781']
                    ])
                    ->add('legalCnss', TextType::class, [
                        'label' => 'Numéro CNSS',
                        'required' => false,
                        'property_path' => 'legalInfos[cnss]',
                        'attr' => ['placeholder' => '21312177']
                    ])
                ->end() // Ferme le groupe 'Données Légales'
            ->end(); // Ferme l'onglet 'Informations Juridiques'
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('emailContact', FieldDescriptionInterface::TYPE_ARRAY, [
                'label' => 'Emails',
            'display' => 'values',
                'template' => 'bundles/SonataAdminBundle/CRUD/list_custom_array.html.twig'
            ])
            ->add('phonePrimary', FieldDescriptionInterface::TYPE_ARRAY, [
                'label' => 'Téléphones',
            'display' => 'values',
                'template' => 'bundles/SonataAdminBundle/CRUD/list_custom_array.html.twig'
            ])
            ->add('legalInfos', FieldDescriptionInterface::TYPE_ARRAY, [
                'label' => 'Identifiants Légaux',
            'display' => 'values',
                'template' => 'bundles/SonataAdminBundle/CRUD/list_legal_infos.html.twig'
            ])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'label'   => 'Actions',
                'actions' => [
                    'show'   => [
                        'icon' => 'eye',
                    ],
                    'edit'   => [
                        'icon' => 'edit',
                    ],
                ],
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->with('Détails de l\'entreprise')
            ->add('emailContact', null, [
                'label' => 'Liste des Emails',
                'template' => 'bundles/SonataAdminBundle/CRUD/show_custom_array.html.twig'
            ])
            ->add('phonePrimary', null, [
                'label' => 'Liste des Téléphones',
                'template' => 'bundles/SonataAdminBundle/CRUD/show_custom_array.html.twig'
            ])
            ->add('addresses', null, [
                'label' => 'Adresses',
                'template' => 'bundles/SonataAdminBundle/CRUD/show_custom_array.html.twig'
            ])
            ->end()
            ->with('Informations Juridiques', ['class' => 'col-md-12'])
                ->add('legalInfos', null, [
                    'label' => 'Détails Légaux (IFU, RCCM, CNSS)',
                    'template' => 'bundles/SonataAdminBundle/CRUD/show_legal_infos.html.twig'
                ])
            ->end()
            ;
    }

}