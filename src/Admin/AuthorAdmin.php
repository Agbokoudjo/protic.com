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

use App\Entity\Author;
use App\Repository\AuthorRepository;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\UX\Dropzone\Form\DropzoneType;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter ;
use Sonata\DoctrineORMAdminBundle\Filter\ChoiceFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateFilter ;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
#[AutoconfigureTag(
    name: 'sonata.admin',
    attributes: [
        'id'           => 'app.admin.author',
        'code'         => 'app.admin.author',
        'admin_code'   => 'app.admin.author',
        'model_class'  => Author::class,
        'manager_type' => 'orm',
        'group'        => 'app.admin.group.catalogue',
        'label'        => 'Auteurs',
        'pager_type'   => 'simple',
    ]
)]
final class AuthorAdmin extends WlindablaAdmin
{
    public function __construct(
        private readonly AuthorRepository $authorRepository
    )
    {
        parent::__construct(
            "list__app_admin_author",
            "show__app_admin_author",
            "create__app_admin_author",
            "edit__app_admin_author"
        );
    }

    protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    {
        $query = parent::configureQuery($query);
        $rootAlias = $query->getRootAliases()[0];
        $query->leftJoin($rootAlias . '.books', 'b')
            ->addSelect('COUNT(b.id) numberofbookspublished')
            ->addGroupBy($rootAlias .'.id');

        return $query;
    }

    protected function prePersist(object $object): void
    {
        if (!($object instanceof Author)) {
            return;
        }
        $object->prePersist();
    }

    protected function preUpdate(object $object): void
    {
        if (!($object instanceof Author)) {
            return;
        }
        $object->preUpdate();
    }

    protected function postPersist(object $object): void
    {
        $this->authorRepository->invalidateForEntity($object);
    }
    
    protected function postUpdate(object $object): void
    {
        $this->authorRepository->invalidateForEntity($object);
    }

    protected function postRemove(object $object): void
    {
        $this->authorRepository->invalidateForEntity($object);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('fullName',StringFilter::class,[
                'label' => 'Nom',
                'field_type' => TextType::class,
                'force_case_insensitivity' => true,
                'field_options' => [
                    'attr' => [
                        'placeholder' => 'Filtrer par nom de l\'auteur',
                     ]
                 ]
                ,
                'show_filter' => true,
            ])
            ->add('email', StringFilter::class, [
                'label' => 'Email',
                'field_type' => TextType::class,
                'force_case_insensitivity' => true,
                'field_options' => [
                    'attr' => [
                        'placeholder' => 'Filtrer par email de l\'auteur',
                    ]
                ],
            ])
            ->add('country', ChoiceFilter::class, [
                'label' => 'Pays',
                'field_type' => CountryType::class,
                'field_options' => [
                    'attr' => [
                        'placeholder' => 'Filtrer par pays',
                    ],
                    "alpha3" => true,
                    'choice_translation_domain' => false,
                    'multiple'=>false
                ],
            ])
            ->add('phone', StringFilter::class, [
                'label' => 'Téléphone',
                'field_options' => [
                    'attr' => [
                        'placeholder' => 'Filtrer par numéro de téléphone',
                    ]
                ],
                'show_filter' => true,
            ])
            ->add('createdAt', DateFilter::class, [
                'label' => 'Date de créations',
                'field_type' => DateType::class,
                'field_options' => [
                    'widget' => 'single_text'
                ],
            ])
        ;
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('avatarFile', VichImageType::class, [
                'label' => 'PHOTO',
                'template' => 'bundles/SonataAdminBundle/CRUD/list_image.html.twig',
                'header_style' => 'width:10%',
            ])
            ->add('fullName', FieldDescriptionInterface::TYPE_STRING, [
                'label'    => 'Nom complet',
                'sortable' => true,
            ])
            ->add('email', FieldDescriptionInterface::TYPE_STRING, [
                'label'    => 'Email',
                'sortable' => true,
            ])
            ->addIdentifier('numberofbookspublished', null, [
                'label' => 'Livres publiés',
                'template' => 'bundles/SonataAdminBundle/CRUD/list_author_books_count.html.twig'
            ])
            ->add('bio', FieldDescriptionInterface::TYPE_TEXTAREA, [
                'label' => 'Biographie',
                'header_style' => 'width: 25%',
                'template' => 'bundles/SonataAdminBundle/CRUD/list_textarea.html.twig'
        ])
            
            ->add(ListMapper::NAME_ACTIONS, null, [
                'label'   => 'Actions',
                'actions' => [
                    'show'   => ['icon' => 'bi bi-eye'],
                    'edit'   => ['icon' => 'bi bi-pencil-square'],
                    'delete' => ['icon' => 'bi bi-trash3'],
                ],
            ]);
    }
    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'sonata_author';
    }

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'author';
    }

    public function toString(object $object): string
    {
        return $object instanceof Author && $object->getFullName()
            ? sprintf('✍️ %s', $object->getFullName())
            : 'Nouvel auteur';
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->with('Détails de l\'auteur', [
                'icon'        => 'bi bi-person-badge',
                'description' => 'Informations complètes sur cet auteur.',
            ])
            
            ->add('fullName', null, [
                'label' => 'Nom complet',
            ])
            ->add('email', null, [
                'label' => 'Email',
            ])
            ->add('phone', null, [
                 'label' => 'Téléphone',
                 'template' => 'bundles/SonataAdminBundle/CRUD/show_phone_number.html.twig'
            ])
            ->add('country', null, [
                'label' => 'Pays',
                'template' => 'bundles/SonataAdminBundle/CRUD/show_country_name.html.twig'
            ])
            ->add('bio', null, [
                'label' => 'Biographie',
            ])
            ->add('createdAt', FieldDescriptionInterface::TYPE_DATETIME, [
                'label'   => 'Créé le',
                'inline'  => true,
                'display' => 'both',
                'format'  => 'd/m/Y à H:i:s',
            ])
            ->add('updatedAt', FieldDescriptionInterface::TYPE_DATETIME, [
                'label'   => 'Dernière modification',
                'inline'  => true,
                'display' => 'both',
                'format'  => 'd/m/Y à H:i:s',
            ])
            ->end()

            ->with('Livres publiés', [
                'icon'        => 'bi bi-book',
                'description' => 'Liste des livres écrits par cet auteur.',
            ])
            ->add('books', null, [
                'label' => 'Livres de cet auteur',
            ])
            ->end();
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $isCreate = $this->getSubject()?->getId() === null;

        $form
            ->with(
                $isCreate ? 'Création d\'un nouvel auteur' : 'Modification de l\'auteur',
                [
                    'class'       => 'col-md-8',
                    'icon'        => 'bi bi-person-lines-fill',
                    'box_class'   => 'box box-solid box-solid-with',
                    'description' => $isCreate
                        ? 'Renseignez les informations de l\'auteur.'
                        : 'Modifiez les informations de l\'auteur.',
                ]
            )
            ->add('fullName', TextType::class, [
                'label'    => 'Nom complet',
                'required' => true,
            'label_attr' => ['class' => 'form-label'],
                'attr'     => [
                    'placeholder'                       => 'Ex: Victor Hugo, Ahmadou Kourouma...',
                    'autocomplete'                      => 'name',
                    'minlength'                         => 6,
                    'maxlength'                         => 255,
                    'data-pattern'                      => '^[\p{L}\p{N}\p{M}\s\-\.]+$',
                    'data-eg-await'                     => 'Victor Hugo, Mongo Beti',
                    'data-escapestrip-html-and-php-tags' => 'true',
                    'data-position-lastname' => "left",
                    'data-event-validate-blur'          => 'blur',
                    'data-event-validate-input'         => 'input',
                    'data-error-message-input'          => 'Le nom ne peut contenir que des lettres, chiffres, espaces et tirets.',
                ],
                'help'     => 'Nom et prénom complets. Unique, entre 6 et 255 caractères.',
            ])
            ->add('email', EmailType::class, [
                'label'    => 'Adresse email',
                'required' => true,
                'attr'     => [
                    'placeholder' => 'Ex: auteur@example.com',
                    'minlength' => 6,
                    'data-type' => "email",
                    'autocomplete'                      => 'email',
                    'maxlength'                         => 200,
                    'data-escapestrip-html-and-php-tags' => 'true',
                    'data-event-validate-blur'          => 'blur',
                    'data-event-validate-input'         => 'input',
                    'data-error-message-input'          => 'Veuillez saisir une adresse email valide.',
                ],
                'help'     => 'Email unique de l\'auteur.',
                'label_attr' => ['class' => 'form-label'],
            ])
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
            ->add('bio', TextareaType::class, [
                'label'    => 'Biographie',
                'required' => true,
                'attr'     => [
                    'placeholder'                        => 'Rédigez une courte biographie de l\'auteur...',
                    'rows'                               => 6,
                    'minlength'                          => 20,
                    'maxlength'                          => 4000,
                    'data-type'                          => 'textarea',
                     'data-pattern'      => '<[^>]*>|<\/[^>]+>|&[#a-zA-Z0-9]+;|javascript\s*:|data\s*:|vbscript\s*:|on\w+\s*=|<\?(?:php)?|\?>|\{\{.*?\}\}|\$\{',
                    'data-match'        => 'false',
                    'data-flag-pattern' => 'ius',
                    'data-escapestrip-html-and-php-tags' => 'true',
                    'data-event-validate-blur'           => 'blur',
                    'data-event-validate-input'          => 'input',
                    'data-error-message-input'           => 'La biographie ne peut pas contenir de balises HTML. Entre 20 et 4000 caractères.',
                ],
                'help' => 'Entre 20 et 4000 caractères. Les balises HTML sont interdites.',
            ])
            ->end()

            // ── Section avatar + pays ─────────────────────
            ->with('Avatar & Localisation', [
                'class'       => 'col-md-4',
                'icon'        => 'bi bi-image',
                'box_class'   => 'box box-solid box-solid-with',
                'description' => 'Photo de profil et pays d\'origine de l\'auteur.',
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
        
            ->add('country', CountryType::class, [
                'label'  => 'Pays',
                'help'        => 'Pays d\'origine ou de résidence de l\'auteur.',
                'label_attr' => ['class' => 'form-label'],
                'required' => true,
                "alpha3" => true,
                'placeholder' => 'Sélectionnez un pays...',
                'choice_translation_domain' => false,
                'multiple'=>false,
                'attr' => [
                    'data-escapestrip-html-and-php-tags' => true,
                    'data-event-validate-change' => 'change',
                    'data-event-validate-blur' => 'blur',
                    'class' => 'form-control select2 form-select-lg form-control-lg',
                    'data-minimumInputLength' => 1,
                    'data-error-message-input' => 'Veuillez sélectionner un pays.',
                    'data-sonata-select2-maximumSelectionLength'=>1
                ]
            ])
            ->end();
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        
    }

    protected function configureExportFields(): array
    {
        return array_merge(
            parent::configureExportFields(),
            ["books.title"]
        );
    }
}
