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

use App\Controller\Admin\AdminUserCRUDController;
use App\Entity\BaseUserInterface;
use App\Entity\SonataUser;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\BooleanFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateFilter;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\UX\Dropzone\Form\DropzoneType;
use Vich\UploaderBundle\Form\Type\VichImageType;

/**
 * Administration des utilisateurs Sonata (back-office ProTIC).
 *
 * Règles métier :
 *  - Tout utilisateur créé via ce formulaire est automatiquement activé (enabled = true).
 *  - Les utilisateurs créés via fixtures sont désactivés par défaut.
 *  - Le mot de passe est hashé dans prePersist / preUpdate.
 *  - La suppression physique est désactivée (soft-delete via Gedmo).
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 */
#[AutoconfigureTag(
    name: 'sonata.admin',
    attributes: [
        'id'           => 'app.admin.sonata_user',
        'code'         => 'app.admin.sonata_user',
        'admin_code'   => 'app.admin.sonata_user',
        'model_class'  => SonataUser::class,
        'manager_type' => 'orm',
        'group'        => 'app.admin.group.user',
        'label'        => 'Utilisateurs',
        'pager_type'   => 'simple',
    ]
)]
final class SonataUserAdmin extends WlindablaAdmin
{
    public function __construct(
    ) {
        parent::__construct(
            'Liste des utilisateurs',
            'Détails de l\'utilisateur',
            'Créer un utilisateur', 
            'Modifier l\'utilisateur',
        );
    }

    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'sonata_admin_user';
    }

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'admin_user';
    }

    protected function configureRoutes(RouteCollectionInterface $collectionRoutes): void
    {
        parent::configureRoutes($collectionRoutes);

        $collectionRoutes->add(
            name: 'toggle_enabled_user_account',
            pattern: 'toggle_enabled_user_account/{id}',
            defaults: [
                '_controller' => AdminUserCRUDController::class . '::toggleEnabledUserAccountAction',
                '_sonata_admin' => $this->getCode(),
            ],
            methods: ['PATCH'],
        );

        $collectionRoutes->add(
            name: 'resend_email_verify',
            pattern: 'resend_email_verify/{id}',
            defaults: [
                '_controller' => AdminUserCRUDController::class . '::resendEmailVerifyAction',
                '_sonata_admin' => $this->getCode(),
            ],
            methods: ['GET'],
        );

        $collectionRoutes->add(
            name: 'regenerate_tempory_password_user',
            pattern: 'regenerate_tempory_password_user/{id}',
            defaults: [
                '_controller' => AdminUserCRUDController::class . '::regenerateTemporyPasswordUserAction',
                '_sonata_admin' => $this->getCode(),
            ],
            methods: ['PATCH'],
        );

        $collectionRoutes->remove('delete');
    }

    protected function prePersist(object $object): void
    {
        /** @var SonataUser $object */
        $object->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
    }

    protected function preValidate(object $object): void
    {
        $object->setRoles(['ROLE_ADMIN','ROLE_ASSISTANCE']) ;
    }

    protected function preUpdate(object $object): void
    {
        /** @var SonataUser $object */
        $object->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $isEdit = ($this->getSubject()?->getId() !== null);

        $form
            ->with('Identité', ['class' => 'col-md-6'])
            ->add('username', TextType::class, [
                'label'    => 'Nom et Prénoms',
                'attr'     => [
                    'placeholder' => 'ex: AGBOKOUDJO Franck',
                    'autocomplete' => 'on',
                    'minlength' => 6,
                    'maxlength' => 255,
                    'data-pattern' => '^[\p{L}\p{N}\p{M}\s\.]+$',
                    'data-eg-await' => 'WLINDABABLA Empedocle Brondelle',
                    'data-escapestrip-html-and-php-tags' => true,
                    'data-event-validate-blur' => 'blur',
                    'data-event-validate-input' => 'input',
                    'data-position-lastname' => "left",
                    'class' => 'username',
                    'data-error-message-input' => 'Le nom ne peut contenir que des lettres, chiffres, espaces'
                    ],
            'help'     => 'Nom et prénom complets. Unique, entre 6 et 255 caractères.',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr'  => [
                    'placeholder' => 'email@proticeditions.com',
                    'autocomplete' => 'on',
                    'data-escapestrip-html-and-php-tags' => false,
                    'data-event-validate-blur' => 'blur',
                    'data-event-validate-input' => 'input',
                    'data-type' => "email",
                    'minlength' => 6,
                    'maxlength' => 200,
                    'data-eg-await' => 'franckagbokoudjo301@gmail.com',
                    'data-allow-quoted-local' => 'false',
                    'data-host-whitelist' => "gmail.com,yahoo.com",
                    'data-error-message-input'          => 'Veuillez saisir une adresse email valide.',
                    ],
            ])
            ->add('profile', TextType::class, [
                'label'    => 'Profil',
                'required' => false,
                'attr'     => [
                    'placeholder' => 'ex: Directeur ProTIC',
                    'autocomplete' => 'on',
                    'minlength' => 6,
                    'maxlength' => 200,
                    'data-pattern' => '^[\p{L}\p{N}\p{M}\s\-\.&]+$',
                    'data-eg-await' => 'Directeur ProTIC',
                    'data-escapestrip-html-and-php-tags' => true,
                    'data-event-validate-blur' => 'blur',
                    'data-event-validate-input' => 'input',
                    'data-error-message-input' => 'Le profile ne peut contenir que des lettres (toutes langues), chiffres, espaces, tirets, et points.'
                    ],
            ])
            ->end()
            ->with('Sécurité', [
                'class' => 'col-md-6',
            'description' => 'Détails de contact et profil',
            'box_class'   => 'box box-solid box-solid-with',
                'label'=> 'Informations sociales'])
            ->add('phone', PhoneNumberType::class, [
                'label'       => 'Téléphone',
                'required'    => true,
                'default_region' => 'BJ',
                'format'      => \libphonenumber\PhoneNumberFormat::INTERNATIONAL,
                'attr'        => [
                    'placeholder'                       => 'Ex: +229 01 67 25 18 86',
                    'data-escapestrip-html-and-php-tags' => 'true',
                    'data-event-validate-blur'          => 'blur',
                    'minlength' => 8,
                    'maxlength' => 80,
                    'data-type'  => 'tel',
                    "data-eg-await" => '+229 XX XX XX XX',
                    'data-error-message-input'          => 'Numéro de téléphone invalide.',
                ],
                'help'        => 'Numéro international (ex: +229 01 67 25 18 86).',
            ])
            ->add('country', CountryType::class, [
                'label' => 'Pays d\'origine ou de résidence',
                'label_attr' => ['class' => 'form-label'],
                'required' => true,
                "alpha3" => true,
                'choice_translation_domain' => false,
                'attr' => [
                    'data-escapestrip-html-and-php-tags' => true,
                    'data-event-validate-change' => 'change',
                    'data-event-validate-blur' => 'blur',
                    'autocomplete' => 'on',
                    'minlength' => 3,
                    'maxlength' => 150,
                    'data-pattern' => '^[\p{L}\p{M}\s\'-]+$',
                    'class' => 'form-control select2 form-select',
                    'data-minimumInputLength' => 1
                ]
            ])
            ->add('avatarFile', DropzoneType::class, [
                'label'             => 'Photo de profil',
                'label_attr' => ['class' => 'form-label'],
                'required'          => false,
                'attr'              => [
                    'accept' => 'image/jpeg,image/png,image/webp',
                    'data-event-validate-blur' => 'blur',
                    'data-event-validate-change' => 'change',
                    'data-media-type' => 'image',
                    'data-type' => 'file',
                    'data-extentions' => 'jpg,png,jpeg,webp',
                    'data-unity-max-size-file' => 'MiB',
                    'data-maxsize-file' => 2,
                    'data-allowed-mime-type-accept' => 'image/jpeg,image/png,image/webp',
                    'data-min-width' => 50,
                    'data-max-width' => 800,
                    'data-min-height' => 80,
                    'data-max-height' => 800,
                    'placeholder'
                    => "Faites glisser votre fichier d'image ou cliquez pour parcourir.
                        Format photo d'identité :
                        - Hauteur maximale : 800px
                        - Largeur maximale : 800px
                        - Hauteur minimale : 50px
                        - Largeur minimale : 50px"
                ],
                'help'              => 'Formats acceptés : JPG, PNG, WEBP. Max 2 Mo.',
            ])
            /*->add('roles', ChoiceType::class, [
                'label'    => 'Rôles',
                'choices'  => [
                    'Administrateur'       => 'ROLE_ADMIN',
                    'Super Administrateur' => 'ROLE_SUPER_ADMIN',
                    'Éditeur'              => 'ROLE_EDITOR',
                ],
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('enabled', CheckboxType::class, [
                'label'    => 'Compte actif',
                'required' => false,
            ])*/
            ->end();
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $translationDomain = 'SonataUserBundle';

        $filter
            ->add('username', StringFilter::class, [
                'label'                   => 'Nom Complet',
                'field_type'              => TextType::class,
                'force_case_insensitivity' => true,
                'field_options'           => ['attr' => ['placeholder' => 'Rechercher par nom et prénom …']],
                'show_filter'             => true,
            ])

            ->add('email', StringFilter::class, [
                'label'                   => 'Email',
                'field_type'              => TextType::class,
                'force_case_insensitivity' => true,
                'show_filter'             => true,
            'field_options' => [
                'attr' => [
                    'placeholder' => 'Filtrer par email',
                ]
            ],
            ])
            ->add('enabled', BooleanFilter::class, [
                'label' => 'filter.label_enabled',
                'translation_domain' => $translationDomain,
                'treat_null_as' => true,
                'field_options' => [
                    'attr' => [
                        'class' => 'form-control',
                    ]
                ],
            ])
            ->add('lastLogin', DateFilter::class, [
                'label' => 'filter.label_lastLogin',
                'translation_domain' => $translationDomain,
                'field_type' => DateType::class,
                'field_options' => [
                    'widget' => 'single_text',
                ],
            ])
            ->add('emailVerified', BooleanFilter::class, [
                'label' => 'filter.label_email_verified',
                'translation_domain' => $translationDomain
            ])
            ->add('profile', StringFilter::class, [
                'label' => 'filter.label_profile',
                'translation_domain' => $translationDomain,
                'field_options' => [
                    'attr' => [
                        'placeholder' => 'filter.help_profile'
                    ]
                ],
            ])
        ;
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('username', null, ['label' => 'Nom et Prénom', 'sortable' => true])
            ->add('avatarFile', VichImageType::class, [
                'label' => 'PHOTO',
                'template' => 'bundles/SonataAdminBundle/CRUD/list_image.html.twig',
                'header_style' => 'width:5%',
            ])
            ->add('email', null, ['label' => 'Email'])
            ->add('profile', null, ['label' => 'Profil'])
            ->add('enabled', 'boolean', [
                'label'    => 'Actif',
                'editable' => true,
                'sortable' => true,
            ])
            ->add('lastLogin', null, [
                'label'  => 'Dernière connexion',
                'format' => 'd/m/Y H:i',
            ])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'label'   => 'Actions',
                'actions' => [
                    'show'   => [],
                    'edit'   => [],
                'toggle_enabled_user_account' => [
                    'template' => 'bundles/SonataAdminBundle/CRUD/list_action_toggle_enabled_user_account.html.twig',
                ],
                'resend_email_verify' => [
                    'template' => 'bundles/SonataAdminBundle/CRUD/list_action_resend_email_verify.html.twig',
                ],
                'regenerate_tempory_password_user' => [
                    'template' => 'bundles/SonataAdminBundle/CRUD/list_action_regenerate_tempory_password_user.html.twig'
                ]
                ],
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->tab('tab_general_info', [
                'label' => 'show.tab_general_info',
                'translation_domain' => 'SonataUserBundle',
            ])
                ->with('group_account_details', [
                    'label' => 'show.group_account_details',
                    'tab' => false,
                    'class' => 'col-md-6 mt-5',
                    'box_class' => 'box box-solid box-solid-with',
                    'translation_domain' => 'SonataUserBundle',
                ])
                    ->add('id', null, [
                        'label' => 'show.label_id',
                        'translation_domain' => 'SonataUserBundle',
                    ])
                    ->add(
                        'username',
                        null,
                        [
                            'label' => 'show.label_username',
                            'translation_domain' => 'SonataUserBundle',
                        ]
                    )
                    ->add('usernameCanonical', null, [
                        'label' => 'show.label_username_canonical',
                        'translation_domain' => 'SonataUserBundle',
                    ])
                    ->add('profile', null, [
                        'label' => 'show.label_profile',
                        'translation_domain' => 'SonataUserBundle'
                    ])
                ->end()

                ->with('group_status_security', [
                    'label' => 'show.group_status_security',
                    'tab' => false,
                    'class' => 'col-md-6 mt-5',
                    'box_class' => 'box box-solid box-solid-with',
                    'translation_domain' => 'SonataUserBundle',
                ])
                    ->add('enabled', 'boolean', [
                        'label' => 'show.label_enabled',
                        'translation_domain' => 'SonataUserBundle',
                    ])
                    ->add('emailVerified', FieldDescriptionInterface::TYPE_BOOLEAN, [
                        'label' => 'show.label_email_verified',
                        'translation_domain' => 'SonataUserBundle',
                    ])
                    ->add('emailVerifiedAt', FieldDescriptionInterface::TYPE_DATETIME, [
                        'label' => 'show.label_email_verified_at',
                        'inline' => true,
                        'display' => 'both',
                        'format' => 'd/m/Y H:i:s',
                        'translation_domain' => 'SonataUserBundle',
                    ])
                    ->add('roles', FieldDescriptionInterface::TYPE_ARRAY, [
                        'label' => 'show.label_roles',
                        'translation_domain' => 'SonataUserBundle',
                        'display' => 'values',
                        'value_translation_domain' => 'role'
                    ])
                    ->add('confirmationToken', null, [
                        'label' => 'show.label_confirmation_token',
                        'translation_domain' => 'SonataUserBundle',
                        'role' => 'ROLE_SUPER_ADMIN'
                    ])
                    ->add('tokenRequestedAt', FieldDescriptionInterface::TYPE_DATETIME, [
                        'label' => 'show.label_token_requested_at',
                        'inline' => true,
                        'display' => 'both',
                        'format' => 'd/m/Y H:i:s',
                        'translation_domain' => 'SonataUserBundle',
                    ])
                ->end()
            ->end() // Fin du Tab "Informations Générales"

            // --- 2. TAB : Coordonnées et Fichiers ---
            ->tab('tab_contact_files', [
                'label' => 'show.tab_contact_files',
                'translation_domain' => 'SonataUserBundle'
            ])
                ->with('group_contact_details', [
                    'label' => 'show.group_contact_details',
                    'tab' => false,
                    'class' => 'col-md-6 mt-5',
                    'box_class' => 'box box-solid box-solid-with',
                    'translation_domain' => 'SonataUserBundle',
                ])
                    ->add('email', null, [
                        'label' => 'show.label_email',
                        'translation_domain' => 'SonataUserBundle'
                    ])
                    ->add('emailCanonical', null, [
                        'label' => 'show.label_email_canonical',
                        'translation_domain' => 'SonataUserBundle'
                    ])
                    ->add('phone', null, [
                        'label' => 'show.label_phone',
                        'translation_domain' => 'SonataUserBundle',
                        'template' => 'bundles/SonataAdminBundle/CRUD/show_phone_number.html.twig'
                    ])
                    ->add('country', null, [
                        'label' => 'show.label_country',
                        'translation_domain' => 'SonataUserBundle',
                        'template' => 'bundles/SonataAdminBundle/CRUD/show_country_name.html.twig'
                    ])
                ->end()
                ->with('group_files', [
                    'label' => 'show.group_files',
                    'tab' => false,
                    'class' => 'col-md-6 mt-5',
                    'box_class' => 'box box-solid box-solid-with',
                    'translation_domain' => 'SonataUserBundle',
                ])
                    ->add('avatarFile', VichImageType::class, [
                        'label' => 'show.label_avatar',
                        'template' => 'bundles/SonataAdminBundle/CRUD/show_image.html.twig',
                        'translation_domain' => 'SonataUserBundle',
                    ])
                ->end()
            ->end() // Fin du Tab "Coordonnées et Fichiers"

            // --- 4. TAB : Dates et Historique ---
            ->tab('tab_dates_history', [
                'label' => 'show.tab_dates_history',
                'translation_domain' => 'SonataUserBundle',
            ])
                ->with('group_dates', [
                    'label' => 'show.group_dates',
                    'tab' => false,
                    'class' => 'col-md-6 mt-5',
                    'box_class' => 'box box-solid box-solid-with',
                    'translation_domain' => 'SonataUserBundle',
                ])
                    ->add('createdAt', 'datetime', [
                        'label' => 'show.label_created_at',
                        'inline' => true,
                        'display' => 'both',
                        'format' => 'd/m/Y H:i:s',
                        'translation_domain' => 'SonataUserBundle',
                    ])
                    ->add('updatedAt', 'datetime', [
                        'label' => 'show.label_updated_at',
                        'inline' => true,
                        'display' => 'both',
                        'format' => 'd/m/Y H:i:s',
                        'translation_domain' => 'SonataUserBundle',
                    ])
                    ->add('lastLogin', 'datetime', [
                        'label' => 'show.label_last_login',
                        'inline' => true,
                        'display' => 'both',
                        'format' => 'd/m/Y H:i:s',
                        'translation_domain' => 'SonataUserBundle',
                    ])
                    ->add('avatarUpdatedAt', 'datetime', [
                        'label' => 'show.label_avatar_updated_at',
                        'inline' => true,
                        'display' => 'both',
                        'format' => 'd/m/Y H:i:s',
                        'translation_domain' => 'SonataUserBundle',
                    ])
                    ->add('passwordRequestedAt', 'datetime', [
                        'label' => 'show.label_password_requested_at',
                        'inline' => true,
                        'display' => 'both',
                        'format' => 'd/m/Y H:i:s',
                        'translation_domain' => 'SonataUserBundle',
                    ])
                ->end()
            ->end() // Fin du Tab "Dates et Historique"

        ;
    }

    protected function configureExportFields(): array
    {
        // Avoid sensitive properties to be exported.
        return array_filter(
            parent::configureExportFields(),
            static fn(string $v): bool => !\in_array($v, ['password', 'salt'], true)
        );
    }

    /**
     * Crée la configuration (data) de l'action "Générer Mot de Passe Temporaire".
     *
     * @param BaseUserInterface $object L'entité utilisateur (BaseUserInterface)
     * @return array|null Le tableau de configuration pour le template, ou null si la condition n'est pas remplie.
     */
    final public function createActionGeneratePassword(BaseUserInterface $object): ?array
    {
        if (!$object->isEnabled()) {
            return null;
        }

        return [
            'label' => 'label_regenerate_password',
            'icon' => 'fas fa-key',
            'title' => 'link_title_regenerate_password',

            'url' => $this->generateUrl('regenerate_tempory_password_user', [
                'id' => $object->getId(),
            ]),

            'target' => '_self',
            'confirm' => 'confirm_regenerate_password',

            'extra_class' => 'js-regenerate-password-btn',
        ];
    }

    /**
     * Crée la configuration de l'action de renvoi d'email de vérification.
     * Le bouton n'est affiché que si l'email de l'utilisateur n'est PAS vérifié.
     *
     */
    final public function createResendEmailVerificationAction(BaseUserInterface $object): ?array
    {
        // Condition d'affichage : Si l'email est déjà vérifié, ne pas afficher le bouton.
        if ($object->isEmailVerified()) {
            return null;
        }

        // Configuration pour le bouton (traduisible)
        return [
            'label' => 'label_resend_verification',
            'icon' => 'fas fa-envelope-open-text',
            'title' => 'link_title_resend_verification',
            'url' => $this->generateUrl('resend_email_verify', [
                'id' => $object->getId(),
            ]),
            'target' => '_self',
            'confirm' => 'confirm_resend_verification',
        ];
    }

    /**
     * Crée la configuration de l'action toggle pour l'affichage
     *
     */
    final public function createToggleEnabledAction(BaseUserInterface $object): array
    {
        $isEnabled = $object->isEnabled();

        return [
            'label' => $isEnabled ? 'label_inactive' : 'label_active',
            'icon' => $isEnabled ? 'fas fa-lock' : 'fas fa-lock-open',
            'title' => $isEnabled ? 'link_title_inactive' : 'link_title_active',
            'url' => $this->generateUrl('toggle_enabled_user_account', [
                'id' => $object->getId(),
            ]),
            'target' => '_self',
            'confirm' => $isEnabled ? 'confirm_action_inactive' : 'confirm_action_active',
        ];
    }

}
