<?php
declare(strict_types=1);
/*
 * This file is part of the project by AGBOKOUDJO Franck.
 *
 * (c) AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * Company: INTERNATIONALES WEB APPS & SERVICES
 */

namespace App\Admin;

use App\Admin\WlindablaAdmin;
use App\Entity\Author;
use App\Entity\Book;
use App\Form\AuthorAutocompleteField;
use App\Form\CategoryAutocompleteField;
use App\Repository\BookRepository;
use App\Service\SlugGeneratorService;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\DateFilter;
use Sonata\DoctrineORMAdminBundle\Filter\ModelFilter;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\UX\Dropzone\Form\DropzoneType;
use Vich\UploaderBundle\Form\Type\VichImageType;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 */
#[AutoconfigureTag(
    name: 'sonata.admin',
    attributes: [
        'id'           => 'app.admin.book',
        'code'         => 'app.admin.book',
        'model_class'  => Book::class,
        'manager_type' => 'orm',
        'group'        => 'app.admin.group.catalogue',
        'label'        => 'Livres',
        'pager_type'   => 'simple',
    ]
)]
final class BookAdmin extends WlindablaAdmin
{
    public function __construct(
        private readonly BookRepository $bookRepository,
        private readonly SlugGeneratorService $slugGenerator,
    )
    {
        parent::__construct(
            "list__app_admin_book",
            "show__app_admin_book",
            "create__app_admin_book",
            "edit__app_admin_book"
        );
    }


    protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    {
        $query = parent::configureQuery($query);
        $query
            ->addSelect('au','cat', 'o')
            ->leftJoin('o.category', 'cat')
            ->leftJoin('o.author', 'au')
            ->groupBy('o.id, au.id,cat.id')
        ;

        return $query;
    }

    protected function prePersist(object $object): void
    {
        if ($object instanceof Book) {
            $object->prePersist();
            $this->slugGenerator->updateSlug($object);
        }
    }

    protected function preUpdate(object $object): void
    {
        if ($object instanceof Book) {
            $object->preUpdate();
            $this->slugGenerator->updateSlug($object,true);
        }
    }


    protected function postPersist(object $object): void
    {
        $this->bookRepository->invalidateForEntity($object);
    }

    protected function postUpdate(object $object): void
    {
        $this->bookRepository->invalidateForEntity($object);
    }

    protected function postRemove(object $object): void
    {
        $this->bookRepository->invalidateForEntity($object);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
          
            ->add('title', StringFilter::class, [
            'label' => 'Titre',
            'field_type' => TextType::class,
            'force_case_insensitivity' => true,
            'field_options' => [
                'attr' => [
                    'placeholder' => 'Filtrer par titre du livre',
                ]
            ],
            'show_filter' => true,
        ])
            ->add('subtitle', StringFilter::class, [
            'label' => 'Sous Titre',
            'field_type' => TextType::class,
            'force_case_insensitivity' => true,
            'field_options' => [
                'attr' => [
                    'placeholder' => 'Filtrer par sous titre du livre',
                ]
            ],
        ])
           
            ->add('isbn', StringFilter::class, [
            'label' => 'ISBN',
            'field_type' => TextType::class,
            'force_case_insensitivity' => true,
            'field_options' => [
                'attr' => [
                    'placeholder' => 'Filtrer par numéro isbn',
                ]
            ],
            'show_filter' => true,
        ])
            ->add('author', ModelFilter::class, [
                'label' => 'Auteur',
            'field_type' => AuthorAutocompleteField::class,
            'field_options' => [
                'class' => Author::class,
                'autocomplete' => true,
                'attr' => [
                    'placeholder' => 'Filtrer par nom de l\'auteur...',
                ],
            ]  
            ])
            ->add('publishedAt', DateFilter::class, [
            'label' => 'Date de Publicattion',
            'field_type' => DateType::class,
            'field_options' => [
                'widget' => 'single_text'
            ],
        ])
            ->add('createdAt', DateFilter::class, [
            'label' => 'Date d\'ajout dans le tableau de bord',
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
            ->add('coverFile', VichImageType::class, [
                'label' => 'COUVERTURE',
               'template' => 'bundles/SonataAdminBundle/CRUD/list_coverImage.html.twig',
                'header_style' => 'width:10%',
            ])
            ->add('title', FieldDescriptionInterface::TYPE_STRING, [
                'label' => 'Titre',
                'sortable' => true,
            ])
            ->add('author', null, [
                'label' => 'Auteur',
                'sortable' => true,
            ])
            ->add('category', null, [
                'label' => 'Catégorie',
                'sortable' => true,
            ])
            ->add('isbn', null, [
                'label' => 'ISBN',
            ])
            ->add('publishedAt', FieldDescriptionInterface::TYPE_DATETIME, [
                'label' => 'Publié le',
                'format' => 'd/m/Y H:i:s',
            ])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'label' => 'Actions',
                'actions' => [
                    'show' => ['icon' => 'bi bi-eye'],
                    'edit' => ['icon' => 'bi bi-pencil-square'],
                    'delete' => ['icon' => 'bi bi-trash3'],
                ],
            ]);
    }


    protected function configureFormFields(FormMapper $form): void
    {
        $isCreate = $this->getSubject()?->getId() === null;

        $form
            ->with('Informations Générales', [
                'class' => 'col-md-7',
                'icon' => 'bi bi-book-half',
                 'box_class' => 'box box-solid box-solid-with',
                'description' => 'Détails principaux de l\'ouvrage.',
            ])
            ->add('title', TextType::class, [
                'label'    => 'Titre du livre',
                'help'     => 'Tous caractères sauf balises HTML et symboles dangereux. Entre 4 et 255 caractères.',
                'required' => true,
                'attr'     => [
                    'placeholder'                        => 'Ex: L\'enfant noir',
                    'minlength'                          => 4,
                    'maxlength'                          => 255,
                    'data-event-validate-input'          => 'input',
                    'data-event-validate-blur'           => 'blur',
                   'data-pattern' => '^[\p{L}\p{N}\p{M}\p{P}\s\-\.]+$',
                    'data-match'        => 'true',
                    'data-flag-pattern' => 'iu',
                    'data-escapestrip-html-and-php-tags' => 'true',
                    'data-error-message-input'           => 'Le titre ne peut pas contenir de balises HTML ou symboles dangereux. Entre 4 et 255 caractères.',
                ],
            ])
            ->add('subtitle', TextType::class, [
                'label'    => 'Sous-titre',
                'required' => false,
                'attr'     => [
                    'placeholder'                        => 'Optionnel...',
                    'maxlength'                          => 255,
                    'data-event-validate-input'          => 'input',
                    'data-event-validate-blur'           => 'blur',
                    'data-pattern' => '^[\p{L}\p{N}\p{M}\p{P}\s\-\.]+$',
                    'data-match'        => 'true',
                    'data-flag-pattern' => 'iu',
                    'data-escapestrip-html-and-php-tags' => 'true',
                    'data-error-message-input'           => 'Le sous-titre contient des caractères interdits.',
                ],
            ])
            ->add('isbn', TextType::class, [
                'label'    => 'Code ISBN',
                'required' => false,
                'help'     => 'ISBN-10 (ex: 2-266-11156-3) ou ISBN-13 (ex: 978-2-266-11156-0)',
                'attr'     => [
                    'placeholder'               => 'Ex: 978-2-266-11156-0 ou 2-266-11156-3',
                    'maxlength'                 => 17,
                    'data-type'                 => 'text',
                    'data-match'                => "true",
                    'data-pattern'              => '^[0-9\-\s]+$',
                    'data-event-validate-blur'  => 'blur',
                    'data-event-validate-input' => 'input',
                    'data-error-message-input'  => 'Format ISBN invalide. Exemples : 978-2-266-11156-0 ou 2-266-11156-3.',
                ],
            ])
            ->add('summary', TextareaType::class, [
                'label'    => 'Résumé (Synopsis)',
                'required' => true,
                'attr'     => [
                    'rows'                               => 8,
                    'minlength'                          => 100,
                    'maxlength'                          => 10000,
                    'placeholder'                        => 'Rédigez un résumé captivant (min 100 caractères)...',
                    'data-type'                          => 'textarea',
                    'data-pattern' => '^[\p{L}\p{N}\p{M}\p{P}\s\-\.]+$',
                    'data-match'        => 'true',
                    'data-flag-pattern' => 'iu',
                    'data-event-validate-blur'           => 'blur',
                    'data-event-validate-input'          => 'input',
                    'data-escapestrip-html-and-php-tags' => 'true',
                    'data-error-message-input'           => 'Le résumé ne peut pas contenir de balises HTML ou caractères spéciaux dangereux. Entre 100 et 5000 caractères.',
                ],
                'help' => 'Entre 100 et 10000 caractères. Les balises HTML sont interdites.',
            ])
            ->end()

            ->with('Classification & Médias', [
                'class' => 'col-md-5',
                'icon' => 'bi bi-tags',
                'description' => 'Fichiers et métadonnées.',
            'box_class' => 'box box-solid box-solid-with'
            ])
            ->add('author', AuthorAutocompleteField::class, [
                'label' => 'Auteur',
                'required' => true,
                'autocomplete' => true,
                'placeholder' => 'Choisir un auteur...',
                'attr' => [
                    'data-escapestrip-html-and-php-tags' => true,
                    'data-event-validate-change' => 'change',
                    'data-event-validate-blur' => 'blur',
                    'class' => 'form-control select2 form-select-lg form-control-lg',
                    'data-minimumInputLength' => 1,
                    'data-error-message-input' => 'Veuillez sélectionner un auteur.',
                    'data-sonata-select2-maximumSelectionLength' => 1
                    ],
            ])
            ->add('category', CategoryAutocompleteField::class, [
                'label' => 'Catégorie',
                'required' => true,
                'placeholder' => 'Choisir une catégorie...',
                'attr' => [
                    'data-escapestrip-html-and-php-tags' => true,
                    'data-event-validate-change' => 'change',
                    'data-event-validate-blur' => 'blur',
                    'class' => 'form-control select2 form-select-lg form-control-lg',
                    'data-minimumInputLength' => 1,
                    'data-error-message-input' => 'Veuillez sélectionner un auteur.',
                    'data-sonata-select2-maximumSelectionLength' => 1
                    ],
            ])
            ->add('coverFile', DropzoneType::class, [
                'label' => 'Image de couverture',
                'required' => $isCreate,
            'help' => 'Format Portrait requis (ex: 600x900px). Max 5 Mo.',
                'attr' => [
                    'accept' => 'image/jpeg,image/png,image/webp',
                'data-event-validate-blur' => 'blur',
                'data-event-validate-change' => 'change',
                'data-media-type' => 'image',
                'data-type' => 'file',
                'data-extentions' => 'jpg,png,jpeg,webp',
                'data-unity-max-size-file' => 'MiB',
                    'data-min-width' => 400,
                    'data-max-width' => 1200,
                    'data-min-height' => 600,
                    'data-max-height' => 1800,
                'data-maxsize-file' => 5,
                'data-allowed-mime-type-accept' => 'image/jpeg,image/png,image/webp',
                'placeholder'
                => "Faites glisser la couvertue du livre ou cliquez pour parcourir.
                        Format portrait :
                        - Hauteur maximale : 1800px
                        - Largeur maximale : 1200px
                        - Hauteur minimale : 600px
                        - Largeur minimale : 400px"
                ],
            ])
            ->add('publishedAt', DateType::class, [
                'label'    => 'Date de parution',
                'required' => false,
                'input'    => 'datetime',
                'attr'     => [
                    'data-type'                          => 'date',
                    'data-format-date'                   => 'YYYY-MM-DD',  // format natif input[type=date]
                    'data-allow-future'                  => 'false',       // pas de date dans le futur
                    'data-allow-past'                    => 'true',
                    'data-min-date'                      => '1450-01-01',  // premier livre imprimé ~1450
                    'data-eg-await'                      => '1995-06-15',
                    'data-event-validate-blur'           => 'blur',
                    'data-event-validate-input'          => 'input',
                    'data-escapestrip-html-and-php-tags' => 'true',
                    'data-match'                         => 'true',
                    'data-pattern'                       => '^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$',
                    'data-error-message-input'           => 'Date invalide. La date de parution ne peut pas être dans le futur.',
                ],
                'help' => 'Format : JJ/MM/AAAA. La date ne peut pas être dans le futur.',
            ])
            ->end();
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->with('Détails de l\'ouvrage', ['icon' => 'bi bi-info-circle'])
            ->add('title', null, ['label' => 'Titre'])
            ->add('subtitle', null, ['label' => 'Sous-titre'])
            ->add('isbn', null, ['label' => 'ISBN'])
            ->add('summary', null, ['label' => 'Résumé'])
            ->add('publishedAt', null, ['label' => 'Publié le', 'format' => 'd/m/Y'])
            ->end()
            ->with('Relations', ['icon' => 'bi bi-link-45deg'])
            ->add('author', null, ['label' => 'Auteur'])
            ->add('category', null, ['label' => 'Catégorie'])
            ->end();
    }

    public function toString(object $object): string
    {
        return $object instanceof Book && $object->getTitle()
            ? sprintf('📖 %s', $object->getTitle())
            : 'Nouveau livre';
    }

    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'sonata_book';
    }

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'book';
    }

    protected function configureExportFields(): array
    {
        return array_merge(
            parent::configureExportFields(),
            ["author.fullName", "category.name"]
        );
    }
}
