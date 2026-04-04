<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\ContactRequest;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\DateFilter;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
#[AutoconfigureTag(
    name: 'sonata.admin',
    attributes: [
        'id'           => 'app.admin.contactrequest',
        'code'         => 'app.admin.contactrequest',
        'admin_code'   => 'app.admin.contactrequest',
        'model_class'  => ContactRequest::class,
        'manager_type' => 'orm',
        'group'        => 'app.admin.group.load',
        'label'        => 'Demande Réçu',
        'pager_type'   => 'simple',
    ]
)]
final class ContactRequestAdmin extends WlindablaAdmin
{
    public function __construct()
    {
        parent::__construct(
            "list__app_admin_contactrequest",
            "show__app_admin_contactrequest"
        );
    }

     protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    {
        $query = parent::configureQuery($query);
        $rootAlias = $query->getRootAliases()[0];

        $query
            ->addSelect('b',$rootAlias)
              ->leftJoin($rootAlias . '.book', 'b')
            ->addGroupBy($rootAlias . '.id', 'b.id')
;
        return $query;
    }

    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'sonata_contactrequest';
    }

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'contactrequest';
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('create');
        $collection->remove('edit');
    }
    
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('fullName', StringFilter::class, [
                'label' => 'Nom',
                'field_type' => TextType::class,
                'force_case_insensitivity' => true,
                'field_options' => [
                    'attr' => [
                        'placeholder' => 'Filtrer par nom',
                    ]
                ],
                'show_filter' => true,
            ])
            ->add('email', StringFilter::class, [
                'label' => 'Email',
                'field_type' => TextType::class,
                'force_case_insensitivity' => true,
                'field_options' => [
                    'attr' => [
                        'placeholder' => 'Filtrer par email',
                    ]
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
            ->add('sentAt', DateFilter::class, [
                'label' => 'Date d\'envoie',
                'field_type' => DateType::class,
                'field_options' => [
                    'widget' => 'single_text'
                ],
            ])
            ->add('subject', StringFilter::class, [
            'label' => 'Sujet',
            'field_options' => [
                'attr' => [
                    'placeholder' => 'Filtrer par objet de message address à l\'auteur',
                ]
            ],
        ])
            ;
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('sentAt', null, [
                'label' => 'Date d\'envoi',
                'format' => 'd/m/Y H:i'
            ])
            ->add('fullName', null, ['label' => 'Nom complet'])
            ->add('email', null, ['label' => 'Email'])
            ->add('phone', null, [
                'label' => 'Téléphone',
                'template' => 'bundles/SonataAdminBundle/CRUD/list_phone_number.html.twig'
            ])
            ->add('status', 'badge', [
                'label' => 'Statut',
                //'template' => '@SonataAdmin/CRUD/list_select.html.twig' // Optionnel si tu gères des status
            ])
            ->add('book', null, [
                'label' => 'Livre concerné',
                //'associated_property' => 'title'
            ])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'label' => 'Actions',
                'actions' => [
                    'show' => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->with('Détails de la demande', ['class' => 'col-md-7'])
            ->add('fullName', null, ['label' => 'Expéditeur'])
            ->add('email', null, ['label' => 'Adresse Email'])
            ->add('phone', null, [
                'label' => 'Téléphone',
            'template' => 'bundles/SonataAdminBundle/CRUD/show_phone_number.html.twig'
                ])
            ->add('country', FieldDescriptionInterface::TYPE_STRING, [
                'label'    => 'Pays',
                'sortable' => true,
                'template' => 'bundles/SonataAdminBundle/CRUD/show_country_name.html.twig'
            ])
            ->add('sentAt', null, ['label' => 'Reçu le'])
            ->end()
            ->with('Contenu', ['class' => 'col-md-5'])
            ->add('subject', null, ['label' => 'Sujet'])
            ->add('message', FieldDescriptionInterface::TYPE_TEXTAREA, [
                'label' => 'Message',
                'header_style' => 'width: 30%',
                'template' => 'bundles/SonataAdminBundle/CRUD/show_textarea.html.twig'
            ])
            ->add('book', null, ['label' => 'Livre lié'])
            ->end();
    }

}
