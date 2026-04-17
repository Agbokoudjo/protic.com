<?php

declare(strict_types=1);

/*
 * This file is part of the project by AGBOKOUDJO Franck.
 *
 * (c) AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * Phone: +229 01 67 25 18 86
 * For more information, please feel free to contact the author.
 */

namespace App\Admin;

use App\Controller\Admin\TeamMemberAdminCRUDController;
use App\Entity\TeamMember;
use App\Repository\TeamMemberRepository;
use App\Security\Handler\TeamMemberRoleSecurityHandler;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\BooleanFilter;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\UX\Dropzone\Form\DropzoneType;
use Vich\UploaderBundle\Form\Type\VichImageType;

/**
 * Administration des membres de l'équipe ProTIC Editions & Services.
 *
 * Règles métier :
 *  - Un membre créé via ce formulaire est visible par défaut (visible = true).
 *  - La suppression physique est désactivée (soft-delete via Gedmo).
 *  - L'ordre d'affichage est contrôlé via le champ "position".
 *  - Un membre peut être lié (optionnellement) à un compte SonataUser.
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 */
#[AutoconfigureTag(
    name: 'sonata.admin',
    attributes: [
        'id'           => 'sonata.admin.team_member',
        'code'         => 'sonata.admin.team_member',
        'admin_code'   => 'sonata.admin.team_member',
        'model_class'  => TeamMember::class,
        'manager_type' => 'orm',
        'group'        => 'app.admin.group.user',
        'label'        => 'Équipe',
        'pager_type'   => 'simple',
        'security_handler' => TeamMemberRoleSecurityHandler::class,
        'controller'=>TeamMemberAdminCRUDController::class
    ]
)]
final class TeamMemberAdmin extends WlindablaAdmin
{
    public function __construct(private TeamMemberRepository $repository)
    {
        parent::__construct(
            'Liste des membres',
            'Détails du membre',
            'Ajouter un membre',
            'Modifier le membre',
        );
    }

    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'sonata_admin_team_member';
    }

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'admin_team_member';
    }

    protected function configureRoutes(RouteCollectionInterface $collectionRoutes): void
    {
        parent::configureRoutes($collectionRoutes);

        // Route PATCH pour basculer la visibilité d'un membre sans recharger le formulaire
        $collectionRoutes->add(
            name: 'toggle_visible_team_member',
            pattern: 'toggle_visible/{id}',
            defaults: [
                '_sonata_admin' => $this->getCode(),
                '_controller' => TeamMemberAdminCRUDController::class . '::toggleVisibleTeamMemberAction',
            ],
            methods: ['PATCH'],
        );
    }

    protected function prePersist(object $object): void
    {
        /** @var TeamMember $object */
        $object->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
    }

    protected function preUpdate(object $object): void
    {
        /** @var TeamMember $object */
        $object->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
    }

    protected function postPersist(object $object): void
    {
        $this->repository->invalidateCacheTeamMember();
    }

    protected function postUpdate(object $object): void
    {
        $this->repository->invalidateCacheTeamMember();
    }

    protected function postRemove(object $object): void
    {
        $this->repository->invalidateCacheTeamMember();
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            // ---- Colonne gauche : Identité ----
            ->with('Identité', ['class' => 'col-md-6'])
                ->add('name', TextType::class, [
                    'label' => 'Nom / Intitulé',
                    'attr'  => [
                        'placeholder'                        => 'ex: M. SETONWAN DENIS HOUNGNIMON',
                        'autocomplete'                       => 'on',
                        'minlength'                          => 3,
                        'maxlength'                          => 255,
                        'data-pattern'                       => '^[\p{L}\p{N}\p{M}\s\.\-&\']+$',
                        'data-eg-await'                      => 'Équipe Éditoriale',
                        'data-escapestrip-html-and-php-tags' => true,
                        'data-event-validate-blur'           => 'blur',
                        'data-event-validate-input'          => 'input',
                        'data-error-message-input'           => 'Lettres, chiffres, espaces, tirets et points uniquement.',
                    ],
                    'help'  => 'Nom complet ou intitulé de groupe. Entre 3 et 255 caractères.',
                ])
                ->add('role', TextType::class, [
                    'label' => 'Fonction / Rôle',
                    'attr'  => [
                        'placeholder'                        => 'ex: Directeur & Fondateur',
                        'autocomplete'                       => 'on',
                        'minlength'                          => 3,
                        'maxlength'                          => 255,
                        'data-pattern'                       => '^[\p{L}\p{N}\p{M}\s\.\-&\']+$',
                        'data-eg-await'                      => 'Logistique & Distribution',
                        'data-escapestrip-html-and-php-tags' => true,
                        'data-event-validate-blur'           => 'blur',
                        'data-event-validate-input'          => 'input',
                        'data-error-message-input'           => 'Lettres, chiffres, espaces, tirets et points uniquement.',
                    ],
                    'help'  => 'Poste ou département. Entre 3 et 255 caractères.',
                ])
                ->add('initial', TextType::class, [
                    'label'    => 'Initiale (fallback avatar)',
                    'required' => false,
                    'attr'     => [
                        'placeholder'                        => 'ex: S',
                        'maxlength'                          => 5,
                        'data-eg-await'                      => 'É',
                        'data-escapestrip-html-and-php-tags' => true,
                        'data-event-validate-blur'           => 'blur',
                        'data-event-validate-input'          => 'input',
                        'data-error-message-input'           => '1 à 5 caractères maximum.',
                    ],
                    'help'     => 'Lettre(s) affichées si aucune photo n\'est disponible.',
                ])
                ->add('bio', TextareaType::class, [
                    'label'    => 'Biographie',
                    'required' => false,
                    'attr'     => [
                        'placeholder'                        => 'Courte biographie affichée sur la page À propos...',
                        'rows'                               => 5,
                        'maxlength'                          => 1000,
                        'data-escapestrip-html-and-php-tags' => true,
                        'data-event-validate-blur'           => 'blur',
                        'data-event-validate-input'          => 'input',
                        'data-error-message-input'           => 'Maximum 1000 caractères.',
                    ],
                    'help'     => 'Maximum 1 000 caractères.',
                ])
            ->end()
            ->with('Média & Paramètres', [
                'class'       => 'col-md-6',
                'description' => 'Photo, ordre d\'affichage et visibilité',
                'box_class'   => 'box box-solid box-solid-with',
            ])
                ->add('altText', TextType::class, [
                    'label'    => 'Texte alternatif (alt)',
                    'required' => false,
                    'attr'     => [
                        'placeholder'                        => 'ex: Photo de M. HOUNGNIMON — Directeur ProTIC',
                        'maxlength'                          => 255,
                        'data-escapestrip-html-and-php-tags' => true,
                        'data-event-validate-blur'           => 'blur',
                        'data-event-validate-input'          => 'input',
                        'data-error-message-input'           => 'Maximum 255 caractères.',
                    ],
                    'help'     => 'Description de l\'image pour l\'accessibilité et le SEO.',
                ])
                ->add('imageFile', DropzoneType::class, [
                    'label'    => 'Photo du membre',
                    'required' => false,
                    'attr'     => [
                        'accept'                             => 'image/jpeg,image/png,image/webp',
                        'data-event-validate-blur'           => 'blur',
                        'data-event-validate-change'         => 'change',
                        'data-media-type'                    => 'image',
                        'data-type'                          => 'file',
                        'data-extentions'                    => 'jpg,png,jpeg,webp',
                        'data-unity-max-size-file'           => 'MiB',
                        'data-maxsize-file'                  => 2,
                        'data-allowed-mime-type-accept'      => 'image/jpeg,image/png,image/webp',
                        'data-min-width'                     => 50,
                        'data-max-width'                     => 800,
                        'data-min-height'                    => 80,
                        'data-max-height'                    => 800,
                        'placeholder'                        =>
                            "Glissez-déposez la photo ou cliquez pour parcourir.\n" .
                            "Format recommandé : carré (ex: 400×400px)\n" .
                            "- Largeur : 50px à 800px\n" .
                            "- Hauteur : 80px à 800px",
                    ],
                    'help'     => 'Formats acceptés : JPG, PNG, WEBP. Max 2 Mo.',
                ])
                ->add('position', IntegerType::class, [
                    'label' => 'Ordre d\'affichage',
                    'attr'  => [
                        'min'                                => 0,
                        'placeholder'                        => 'ex: 0',
                        'data-event-validate-blur'           => 'blur',
                        'data-event-validate-input'          => 'input',
                        'data-error-message-input'           => 'Entier positif ou nul.',
                    ],
                    'help'  => '0 = affiché en premier. Plus le chiffre est grand, plus le membre apparaît tard.',
                ])
                ->add('visible', CheckboxType::class, [
                    'label'    => 'Visible sur le site',
                    'required' => false,
                    'help'     => 'Décochez pour masquer ce membre sans le supprimer.',
                ])
                ->add('linkedUser', null, [
                    'label'       => 'Compte admin lié (optionnel)',
                    'required'    => false,
                    'placeholder' => '— Aucun —',
                    'help'        => 'Associer ce membre à un utilisateur de l\'espace d\'administration.',
                ])
            ->end();
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('name', StringFilter::class, [
                'label'                    => 'Nom / Intitulé',
                'field_type'               => TextType::class,
                'force_case_insensitivity' => true,
                'show_filter'              => true,
                'field_options'            => [
                    'attr' => ['placeholder' => 'Rechercher par nom …'],
                ],
            ])
            ->add('role', StringFilter::class, [
                'label'                    => 'Fonction',
                'field_type'               => TextType::class,
                'force_case_insensitivity' => true,
                'field_options'            => [
                    'attr' => ['placeholder' => 'Filtrer par fonction …'],
                ],
            ])
            ->add('visible', BooleanFilter::class, [
                'label'        => 'Visible',
                'treat_null_as' => true,
            ]);
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('id', null, [
                'label'    => '#',
                'sortable' => true,
                'header_style' => 'width:4%',
            ])
            ->add('imageFile', VichImageType::class, [
                'label'        => 'Photo',
                'template'     => 'bundles/SonataAdminBundle/CRUD/list_image.html.twig',
                'header_style' => 'width:6%',
            ])
            ->add('name', null, [
                'label'    => 'Nom / Intitulé',
                'sortable' => true,
            ])
            ->add('role', null, [
                'label'    => 'Fonction',
                'sortable' => true,
            ])
            ->add('visible', 'boolean', [
                'label'    => 'Visible',
                'editable' => true,
                'sortable' => true,
            ])
            ->add('position', null, [
                'label'    => 'Ordre',
                'sortable' => true,
            ])
            ->add('createdAt', null, [
                'label'  => 'Créé le',
                'format' => 'd/m/Y',
            ])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'label'   => 'Actions',
                'actions' => [
                    'show'   => [],
                    'edit'   => [],
                    'delete' => [],
                    'toggle_visible_team_member' => [
                        'template' => 'bundles/SonataAdminBundle/CRUD/list_action_toggle_visible_team_member.html.twig',
                    ],
                ],
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            // TAB 1 — Informations générales
            ->tab('Informations générales')
                ->with('Identité', ['class' => 'col-md-6 mt-5', 'box_class' => 'box box-solid box-solid-with'])
                    ->add('id',       null, ['label' => 'ID'])
                    ->add('name',     null, ['label' => 'Nom / Intitulé'])
                    ->add('role',     null, ['label' => 'Fonction'])
                    ->add('initial',  null, ['label' => 'Initiale'])
                    ->add('bio',      null, ['label' => 'Biographie'])
                ->end()
                ->with('Paramètres', ['class' => 'col-md-6 mt-5', 'box_class' => 'box box-solid box-solid-with'])
                    ->add('visible', FieldDescriptionInterface::TYPE_BOOLEAN, ['label' => 'Visible sur le site'])
                    ->add('position', null, ['label' => 'Ordre d\'affichage'])
                    ->add('linkedUser', null, ['label' => 'Compte admin lié'])
                ->end()
            ->end()

            // TAB 2 — Photo & accessibilité
            ->tab('Photo')
                ->with('Image', ['class' => 'col-md-6 mt-5', 'box_class' => 'box box-solid box-solid-with'])
                    ->add('imageFile', VichImageType::class, [
                        'label'    => 'Photo',
                        'template' => 'bundles/SonataAdminBundle/CRUD/show_image.html.twig',
                    ])
                    ->add('altText', null, ['label' => 'Texte alternatif (alt)'])
                ->end()
            ->end()

            // TAB 3 — Dates
            ->tab('Historique')
                ->with('Dates', ['class' => 'col-md-6 mt-5', 'box_class' => 'box box-solid box-solid-with'])
                    ->add('createdAt', FieldDescriptionInterface::TYPE_DATETIME, [
                        'label'   => 'Créé le',
                        'inline'  => true,
                        'display' => 'both',
                        'format'  => 'd/m/Y H:i:s',
                    ])
                    ->add('updatedAt', FieldDescriptionInterface::TYPE_DATETIME, [
                        'label'   => 'Modifié le',
                        'inline'  => true,
                        'display' => 'both',
                        'format'  => 'd/m/Y H:i:s',
                    ])
                    ->add('imageUpdatedAt', FieldDescriptionInterface::TYPE_DATETIME, [
                        'label'   => 'Photo mise à jour le',
                        'inline'  => true,
                        'display' => 'both',
                        'format'  => 'd/m/Y H:i:s',
                    ])
                ->end()
            ->end();
    }

    /**
     * Génère la configuration du bouton "Basculer visibilité"
     * pour le template list_action_toggle_visible_team_member.html.twig.
     */
    final public function createToggleVisibleAction(TeamMember $object): ?array
    {
        if (!$this->isGranted('ROLE_SUPER_ADMIN') || 
            !$this->isGranted('ROLE_FOUNDER') ||
            !$this->isGranted('ROLE_DIRECTOR') 
        ) {
            return null ;
        }

        $isVisible = $object->isVisible();

        return [
            'label'   => $isVisible ? 'Masquer' : 'Afficher',
            'icon'    => $isVisible ? 'fas fa-eye-slash' : 'fas fa-eye',
            'title'   => $isVisible ? 'Masquer ce membre sur le site' : 'Rendre ce membre visible',
            'url'     => $this->generateUrl('toggle_visible_team_member', ['id' => $object->getId()]),
            'target'  => '_self',
            'confirm' => $isVisible
                ? 'Voulez-vous masquer ce membre sur la page À propos ?'
                : 'Voulez-vous afficher ce membre sur la page À propos ?',
        ];
    }  

    public function toString(object $object): string
    {
        return $object instanceof TeamMember ? $object->getName() ?? 'Membre' : 'Membre';
    }
}
