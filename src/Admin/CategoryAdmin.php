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

use App\Entity\Category;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\String\Slugger\SluggerInterface;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter ;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;

#[AutoconfigureTag(
    name: 'sonata.admin',
    attributes: [
        'id'                  => 'app.admin.category',
        'code'                => 'app.admin.category',
        'admin_code'          => 'app.admin.category',
        'model_class'         => Category::class,
        'manager_type'        => 'orm',
        'group'               => 'app.admin.group.catalogue',
        'label'               => 'Catégories des livres',
        'pager_type'          => 'simple',
    ]
)]
/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
final class CategoryAdmin extends WlindablaAdmin
{
    public function __construct(private readonly SluggerInterface $slugger){
        parent::__construct(
            "app_admin_category", 
            "app_admin_category", 
            "app_admin_category", 
            "app_admin_category") ;
    }

    protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    {
        $query = parent::configureQuery($query);
        $rootAlias = $query->getRootAliases()[0];
        $query
            ->leftJoin($rootAlias . '.books', 'b')
            ->addSelect('COUNT(b.id) numberofbooks')
            ->addGroupBy($rootAlias . '.id');

        return $query;
    }
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('name',StringFilter::class, [
                'label' => 'Nom',
                'force_case_insensitivity' => true,
                'field_type' => TextType::class,
                'field_options' => [
                    'attr' => [
                    'placeholder' => 'Filtrer par nom du categorie',
                ]
            ],
            'show_filter' => true,
            ])
            ->add('slug', null, [
                'label' => 'Slug',
                'force_case_insensitivity' => true,
                'field_type' => TextType::class,
                'field_options' => [
                    'attr' => [
                        'placeholder' => 'Filtrer par slug',
                    ]
                ],
            ]);
    }

    protected function prePersist(object $object):void{
        if(!($object instanceof Category)){ return ; }

       $this->updateSlug($object) ;
        $object->prePersist() ;
    }

    protected function preUpdate(object $object): void
    {
        if (!($object instanceof Category)) { return; }

        $this->updateSlug($object);
        $object->preUpdate();
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('icon', FieldDescriptionInterface::TYPE_STRING, [
                'label'    => 'Icône'
            ])
            ->add('name', FieldDescriptionInterface::TYPE_STRING, [
                'label'    => 'Nom',
                'sortable' => true,
            ])
            ->add('slug', FieldDescriptionInterface::TYPE_STRING, [
                'label'    => 'Slug',
                'sortable' => true,
            ])
            ->addIdentifier('numberofbooks',null,[
                'label'=>'Livres',
                'template'=>'bundles/SonataAdminBundle/CRUD/list_number_of_books.html.twig'
            ])
            ->add('createdAt', FieldDescriptionInterface::TYPE_DATETIME, [
                'label' => 'Créé le',
                'inline' => true,
                'display' => 'both',
                'format' => 'd/m/Y H:i:s', 
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
                    'delete' => [
                        'icon' => 'trash',
                    ],
                ],
            ]);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Informations générales', [
                'class' => 'col-md-8',
                'icon'  => 'bi bi-info-circle',
                 'box_class' => 'box box-solid box-solid-with'
            ])
            ->add('name', TextType::class, [
                'label'    => 'Nom de la catégorie',
                'required' => true, 
                'attr'     => [
                    'placeholder' => 'Ex: Roman, Poésie, Théâtre...',
                    'autocomplete' => 'on',
                    'minlength' => 3,
                    'maxlength' => 100,
                    'data-pattern' => '^[\p{L}\p{N}\s\-\p{P}]+$',
                    'data-eg-await' => 'Roman, Poésie, Théâtre',
                    'data-escapestrip-html-and-php-tags' => 'true',
                    'data-event-validate-blur' => 'blur',
                    'data-event-validate-input' => 'input',
                'data-error-message-input'=> 'Le nom ne peut contenir que des lettres, chiffres, espaces, tirets'
                ],
            ])
            ->add('slug', TextType::class, [
                'label'    => 'Slug (URL)',
                'required' => false,
                'attr'     => [
                    'placeholder' => 'Généré automatiquement si vide',
                    'maxlength' => 120,
                    'data-pattern' => '^[a-z0-9]+(?:-[a-z0-9]+)*$',
                    'data-eg-await' => 'bande-dessinee roman',
                    'data-escapestrip-html-and-php-tags' => 'true',
                    'data-event-validate-blur' => 'blur',
                    'data-event-validate-input' => 'input',
                    'data-flag-pattern'=>'u',
                'data-error-message-input'=> 'Le slug ne peut contenir que des lettres minuscules, des chiffres et des tirets (sans tiret en début, fin ou consécutifs).'
                ],
                'help'     => 'Laissez vide pour générer automatiquement depuis le nom.',
            ])
            ->end()
            ->with('Icône', [
                'class' => 'col-md-4',
                'icon'  => 'bi bi-emoji-smile',
                 'box_class' => 'box box-solid box-solid-with'
            ])
            ->add('icon', TextType::class, [
                'label'    => 'Emoji / Icône',
                'required' => false,
                'attr'     => [
                    'placeholder' => 'Ex: 📖 ✍️ 🎭',
                    'maxlength'   => 10,
                    'data-pattern' => '^[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE00}-\x{FEFF}\x{1F1E0}-\x{1F1FF}\x{200D}\x{20E3}]{1,10}*$',
                    'data-eg-await' => 'bande-dessinee roman',
                    'data-escapestrip-html-and-php-tags' => 'true',
                    'data-event-validate-blur' => 'blur',
                    'data-event-validate-input' => 'input',
                'data-error-message-input'=> 'L\'icône doit être un ou plusieurs emojis valides (max 3).'
                ],
                'help'     => 'Copiez-collez un emoji représentant la catégorie.',
            ])
            ->end();
    }

    // ── Affichage détail ──────────────────────────────────
    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->with('Informations', [
                'icon' => 'bi bi-info-circle',
                'box_class'   => 'box box-solid box-solid-with',
            ])
            ->add('icon', FieldDescriptionInterface::TYPE_STRING, [
                'label' => 'Icône',
            ])
            ->add('name', FieldDescriptionInterface::TYPE_STRING, [
                'label' => 'Nom',
            ])  
            ->add('slug', FieldDescriptionInterface::TYPE_STRING, [
                'label' => 'Slug',
            ])
            ->add('createdAt', FieldDescriptionInterface::TYPE_DATETIME, [
                'label' => 'Créé le',
                'inline' => true,
                'display' => 'both',
                'format' => 'd/m/Y H:i:s',
            ])

            ->add('updatedAt', FieldDescriptionInterface::TYPE_DATETIME, [
                'label' => 'Mise à jour le',
                'translation_domain' => 'date',
                'inline' => true,
                'display' => 'both',
                'format' => 'd/m/Y H:i:s',
            ])
            ->end()
            ->with('Livres associés', [
                'icon' => 'bi bi-book',
                'box_class'   => 'box box-solid box-solid-with',
            ])
            ->add('books', null, [
                'label' => 'Livres dans cette catégorie',
            ])
            ->end();
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('export');
    }

    public function toString(object $object): string
    {
        return $object instanceof Category && $object->getName()
            ? sprintf('📂 %s', $object->getName())
            : 'Nouvelle catégorie';
    }

    public function updateSlug(Category $category): void
    {
        if($category->getSlug()) { return ;}
        
        $slug = $this->slugger->slug($category->getName())->lower()->toString();
        $category->setSlug($slug);
    }

    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'sonata_category';
    }

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'category';
    }
}
