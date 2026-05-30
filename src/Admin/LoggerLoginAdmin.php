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
use App\Entity\LoggerLoginUser; 
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\ChoiceFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateRangeFilter;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;
use Sonata\Form\Type\DateRangeType;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Administration Sonata pour le journal de connexion utilisateur.
 *
 * Cet admin est volontairement en LECTURE SEULE (pas de create/edit/delete)
 * car les logs de connexion constituent un journal d'audit immuable.
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
#[AutoconfigureTag(
    name: 'sonata.admin',
    attributes: [
        'id'                 => 'sonata.admin.logger.login',
        'code'               => 'sonata.admin.logger.login',
        'admin_code'         => 'sonata.admin.logger.login',
        'model_class'        => LoggerLoginUser::class, 
        'manager_type'       => 'orm',
        'group'              => 'sonata.admin.logger',
        'group_code'         => 'sonata.admin.logger',
        'label'              => 'Journal des Connexions',
        'show_in_roles_matrix' => true,
        'pager_type'         => 'simple',
    ]
)]
final class LoggerLoginAdmin extends WlindablaAdmin
{
    public function __construct()
    {
        parent::__construct('label_list_logger_login', 'label_show_logger_login');
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('create');
        $collection->remove('edit');
        $collection->remove('delete');
    }

    protected function configureDatagridFilters(DatagridMapper $datagrid): void
    {
        $translationDomain = 'logger';

        $datagrid
            ->add('username', StringFilter::class, [
                'label'              => 'login.username',
                'field_type'         => TextType::class,
                'translation_domain' => $translationDomain,
                'show_filter'        => true,
            ])
            ->add('email', StringFilter::class, [
                'label'              => 'login.email',
                'field_type'         => TextType::class,
                'translation_domain' => $translationDomain,
            ])
            ->add('lastLoginIp', StringFilter::class, [
                'label'              => 'login.ip_address',
                'field_type'         => TextType::class,
                'translation_domain' => $translationDomain,
                'show_filter'        => false,
            ])
            ->add('createdAt', DateRangeFilter::class, [
                'label'              => 'login.createdAt',
                'field_type'         => DateRangeType::class,
                'translation_domain' => $translationDomain,
                'field_options'      => [
                    'field_options_start' => ['widget' => 'single_text'],
                    'field_options_end'   => ['widget' => 'single_text'],
                ],
            ]);
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('id', FieldDescriptionInterface::TYPE_IDENTIFIER, [
                'label'              => 'login.id',
                'header_style'       => 'width: 5%',
                'translation_domain' => 'logger',
            ])
            ->add('username', FieldDescriptionInterface::TYPE_STRING, [
                'label'              => 'login.username',
                'translation_domain' => 'logger',
            ])
            ->add('email', FieldDescriptionInterface::TYPE_EMAIL, [
                'label'              => 'login.email',
                'translation_domain' => 'logger',
            ])
            ->add('lastLoginIp', FieldDescriptionInterface::TYPE_STRING, [
                'label'              => 'login.lastLoginIp',
                'header_style'       => 'width: 15%',
                'translation_domain' => 'logger',
            ])
            ->add('createdAt', FieldDescriptionInterface::TYPE_DATETIME, [
                'label'              => 'login.createdAt',
                'format'             => 'd/m/Y H:i:s',
                'header_style'       => 'width: 18%; text-align: right',
                'row_align'          => 'right',
                'translation_domain' => 'logger',
            ])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'label'              => 'login.actions',
                'translation_domain' => 'logger',
                'actions'            => [
                    'show' => [],
                ],
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $translationDomain = 'logger';

        $show
            ->with('Audit Info', [
                'class'              => 'col-md-4',
                'label'              => 'login.audit_info',
                'box_class'          => 'box box-solid box-solid-with',
                'translation_domain' => $translationDomain,
            ])
                ->add('id', FieldDescriptionInterface::TYPE_INTEGER, [
                    'label'              => 'login.id',
                    'translation_domain' => $translationDomain,
                ])
                ->add('createdAt', FieldDescriptionInterface::TYPE_DATETIME, [
                    'label'              => 'login.createdAt',
                    'translation_domain' => $translationDomain,
                    'format'             => 'd/m/Y H:i:s',
                ])
            ->end()
            ->with('Informations Utilisateur', [
                'class'              => 'col-md-8',
                'label'              => 'login.info_user',
                'box_class'          => 'box box-solid box-solid-with',
                'translation_domain' => $translationDomain,
            ])
                ->add('username', FieldDescriptionInterface::TYPE_STRING, [
                    'label'              => 'login.username',
                    'translation_domain' => $translationDomain,
                ])
                ->add('email', FieldDescriptionInterface::TYPE_EMAIL, [
                    'label'              => 'login.email',
                    'translation_domain' => $translationDomain,
                ])
            ->end()
            ->with('Contexte Technique', [
                'class'              => 'col-md-12',
                'label'              => 'login.context',
                'box_class'          => 'box box-solid box-solid-with',
                'translation_domain' => $translationDomain,
            ])
                ->add('lastLoginIp', FieldDescriptionInterface::TYPE_STRING, [
                    'label'              => 'login.lastLoginIp',
                    'translation_domain' => $translationDomain,
                ])
            ->end();
    }

    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'sonata_admin_logger_login_user';
    }

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'logger_login_user';
    }

     public function toString(object $object): string
    {
        return $object instanceof LoggerLoginUser && $object->getUsername()
            ? $object->getUsername()
            : ' ';
    }
}
