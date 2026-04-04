<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\ManuscriptSubmission;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\DateFilter;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Vich\UploaderBundle\Form\Type\VichFileType;

#[AutoconfigureTag(
    name: 'sonata.admin',
    attributes: [
        'id'           => 'app.admin.manuscriptsubmission',
        'code'         => 'app.admin.manuscriptsubmission',
        'admin_code'   => 'app.admin.manuscriptsubmission',
        'model_class'  => ManuscriptSubmission::class,
        'manager_type' => 'orm',
        'group'        => 'app.admin.group.load',
        'label'        => 'Demande de Manuscript Reçus',
        'pager_type'   => 'simple',
    ]
)]
final class ManuscriptSubmissionAdmin extends WlindablaAdmin
{
    public function __construct()
    {
        parent::__construct(
            "list__app_admin_manuscriptsubmission",
            "show__app_admin_manuscriptsubmission"
        );
    }

    /**
     * Sécurité : On interdit la création et l'édition manuelle
     */
    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('create');
        $collection->remove('edit');
    }

    /**
     * Vue Liste (Liste des manuscrits reçus)
     */
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('submittedAt', FieldDescriptionInterface::TYPE_DATETIME, [
            'label' => 'Date de dépot',
            'inline' => true,
            'display' => 'both',
            'format' => 'd/m/Y H:i',
        ])
            ->add('fullName', null, ['label' => 'Auteur'])
            ->add('email', null, ['label' => 'Email'])
            ->add('subject', null, ['label' => 'Titre/Objet'])
            ->add('status', 'badge', ['label' => 'Statut'])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'label' => 'Actions',
                'actions' => [
                    'show' => [], 
                    'delete' => [],
                ],
            ]);
    }

    /**
     * Vue détaillée (Show)
     */
    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->with('Informations sur l\'Expéditeur', ['class' => 'col-md-6'])
            ->add('fullName', null, ['label' => 'Nom de l\'auteur'])
            ->add('email', null, ['label' => 'Email'])
            ->add('phone', null, [
                'label' => 'Téléphone',
                'template' => 'bundles/SonataAdminBundle/CRUD/show_phone_number.html.twig'
            ])
            ->add('country', FieldDescriptionInterface::TYPE_STRING, [
                'label'    => 'Pays',
                'sortable' => true,
                'template' => 'bundles/SonataAdminBundle/CRUD/show_country_name.html.twig'
            ])
            ->add('submittedAt', null, ['label' => 'Déposé le'])
            ->end()
            ->with('Le Manuscrit', ['class' => 'col-md-6'])
            ->add('subject', null, ['label' => 'Sujet'])
            ->add('message', null, ['label' => 'Message d\'accompagnement'])
            ->add('manuscriptFile', VichFileType::class, [
                'label' => 'Nom du fichier',
            'template' => 'bundles/SonataAdminBundle/CRUD/show_document.html.twig',
            ])
            ->end();
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('fullName', StringFilter::class, [
            'label' => 'Auteur',
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
            ->add('status', null, ['label' => 'Statut'])
            ->add('submittedAt',DateFilter::class, [
                'label' => 'Date de dépot',
                'field_type' => DateType::class,
                'field_options' => [
                    'widget' => 'single_text'
                ],
        ]);
    }

    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'sonata_manuscriptsubmission';
    }

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'manuscriptsubmission';
    }
   
}
