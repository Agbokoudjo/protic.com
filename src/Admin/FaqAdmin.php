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

use App\Entity\Faq;
use App\Repository\FaqRepository;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\BooleanFilter;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Administration des FAQ ProTIC Éditions.
 * Lecture seule : List + Show uniquement.
 * La création et la modification se font via l'API Platform ou directement en base.
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 */
#[AutoconfigureTag(
    name: 'sonata.admin',
    attributes: [
        'id'           => 'app.admin.faq',
        'code'         => 'app.admin.faq',
        'admin_code'   => 'app.admin.faq',
        'model_class'  => Faq::class,
        'manager_type' => 'orm',
        'group'        => 'app.admin.group.faq',
        'label'        => 'FAQ',
        'pager_type'   => 'simple',
    ]
)]
final class FaqAdmin extends WlindablaAdmin
{
    public function __construct(private readonly FaqRepository $faqRepository)
    {
        parent::__construct(
            'list__app_admin_faq',
            'show__app_admin_faq',
        );
    }

    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'sonata_faq';
    }

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'faq';
    }

    /**
     * Lecture seule : on supprime create, edit et delete.
     * Seuls list et show restent actifs.
     */
    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('create');
        $collection->remove('edit');
        $collection->remove('delete');
    }

    protected function prePersist(object $object): void
    {
        if ($object instanceof Faq) {
            $object->prePersist();
        }
    }

    protected function preUpdate(object $object): void
    {
        if ($object instanceof Faq) {
            $object->preUpdate();
        }
    }

    protected function postPersist(object $object): void
    {
        $this->faqRepository->invalidateForEntity($object);
    }

    protected function postUpdate(object $object): void
    {
        $this->faqRepository->invalidateForEntity($object);
    }

    protected function postRemove(object $object): void
    {
        $this->faqRepository->invalidateForEntity($object);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('question', StringFilter::class, [
                'label'                   => 'Question',
                'field_type'              => TextType::class,
                'force_case_insensitivity' => true,
                'field_options'           => [
                    'attr' => ['placeholder' => 'Rechercher une question…'],
                ],
                'show_filter' => true,
            ])
            ->add('category', StringFilter::class, [
                'label'                   => 'Catégorie',
                'field_type'              => TextType::class,
                'force_case_insensitivity' => true,
                'field_options'           => [
                    'attr' => ['placeholder' => 'Ex : Publication, Tarifs…'],
                ],
                'show_filter' => true,
            ])
            ->add('published', BooleanFilter::class, [
                'label'       => 'Publiée ?'
            ])

            ;
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('id', null, [
                'label'    => '#',
                'sortable' => true,
            ])
            ->add('category', null, [
                'label'    => 'Catégorie',
                'sortable' => true,
            ])
            ->add('question', null, [
                'label'    => 'Question',
                'sortable' => false,
            ])
            ->add('published', 'boolean', [
                'label'    => 'Publiée',
                'sortable' => true,
                'editable' => false,
            ])
            ->add('position', null, [
                'label'    => 'Position',
                'sortable' => true,
            ])
        
            ->add(ListMapper::NAME_ACTIONS, null, [
                'label'   => 'Actions',
                'actions' => [
                    'show' => [],
                ],
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->with('Question & Réponse', ['class' => 'col-md-8'])
            ->add('question', FieldDescriptionInterface::TYPE_TEXTAREA, [
                'label'    => 'Question posée',
                'template' => 'bundles/SonataAdminBundle/CRUD/show_textarea.html.twig',
            ])
            ->add('answer', FieldDescriptionInterface::TYPE_TEXTAREA, [
                'label'    => 'Réponse de l\'administrateur',
                'template' => 'bundles/SonataAdminBundle/CRUD/show_textarea.html.twig',
            ])
            ->end()
            ->with('Informations', ['class' => 'col-md-4'])
            ->add('id', null, ['label' => 'ID'])
            ->add('category', FieldDescriptionInterface::TYPE_STRING, [
                'label' => 'Catégorie',
            ])
            ->add('position', FieldDescriptionInterface::TYPE_INTEGER, [
                'label' => 'Ordre d\'affichage',
            ])
            ->add('published', FieldDescriptionInterface::TYPE_BOOLEAN, [
                'label' => 'Publiée sur le site',
            ])
            ->add('askerEmail', FieldDescriptionInterface::TYPE_EMAIL, [
                'label' => 'Email du visiteur',
            ])
            // ->add('createdAt', FieldDescriptionInterface::TYPE_DATETIME, [
            //     'label'  => 'Créée le',
            //     'format' => 'd/m/Y à H:i',
            // ])
            // ->add('updatedAt', FieldDescriptionInterface::TYPE_DATETIME, [
            //     'label'  => 'Modifiée le',
            //     'format' => 'd/m/Y à H:i',
            // ])
            ->end();
    }
}
