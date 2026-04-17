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
use App\Entity\PermissionRole;
use App\Entity\UserPermissionRole;
use App\Repository\PermissionRoleRepository;
use App\Repository\SonataUserRepository;
use App\Security\Handler\UserPermissionRoleSecurityHandler;
use App\Security\SecurityContextInterface;
use Doctrine\ORM\QueryBuilder;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * @inheritDoc Association dynamique : quel utilisateur (Admin, Ministre, Membre…) possède quel rôle.
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
#[AutoconfigureTag(
    name: 'sonata.admin',
    attributes: [
        'id' => 'sonata.admin.user.permission.role',
        'code' => "sonata.admin.user.permission.role",
        'admin_code' => "sonata.admin.user.permission.role",
        'model_class' => UserPermissionRole::class,
        'manager_type' => 'orm',
        'group' => 'app.admin.group.permission.manager',
        'group_code' => 'app.admin.group.permission.role.manager',
        'label' => 'Attributions des rôles',
        'show_in_roles_matrix' => true,
        'roles' => ['ROLE_ADMIN'] ,
        //'security_handler' => UserPermissionRoleSecurityHandler::class,
        'pager_type' => 'simple'
    ]
)]
final class UserPermissionRoleAdmin extends WlindablaAdmin
{
    public function __construct(
        private readonly SonataUserRepository $adminUserRepository,
        private readonly SecurityContextInterface $securityContextUser)
    {
        parent::__construct('permission.user', 'user_permission_role', 'permission.user');
    }

    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'sonata_admin_user_permission_role';
    }

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'user_permission_role';
    }

    protected function prePersist(object $object): void
    {
        if (!$object instanceof UserPermissionRole) {
            return;
        }
      
        $object->setAssignedAt(new \DateTimeImmutable('now', new  \DateTimeZone('UTC')));
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('id', null, [
                'label' => 'user_permission_role.fields.id',
                'translation_domain' => 'Permission', 
            ])
           
            ->add('roles', FieldDescriptionInterface::TYPE_MANY_TO_ONE, [
                'label' => 'user_permission_role.fields.permission_role',
                'translation_domain' => 'Permission',
                'route' => ['name' => 'show'],
        ])
           
            ->add('userType', FieldDescriptionInterface::TYPE_ENUM, [
                'label' => 'user_permission_role.fields.user_type',
            'translation_domain' => 'Permission',
            'template' => 'bundles/SonataAdminBundle/CRUD/list_user_class.html.twig'
        ])
            ->add('userId', null, [
                'label' => 'user_permission_role.fields.user_id',
            'translation_domain' => 'Permission',
            ])
            ->add('scope', null, [
                'label' => 'user_permission_role.fields.scope',
            'translation_domain' => 'Permission',
            ])
            ->add('assignedAt', FieldDescriptionInterface::TYPE_DATETIME, [
                'label' => 'user_permission_role.fields.assigned_at',
                'format' => 'd/m/Y H:i:s',
                'translation_domain' => 'Permission',
            ])
           ->add('assignedByUser', FieldDescriptionInterface::TYPE_IDENTIFIER,[
                'translation_domain' => 'Permission',
                'label' => 'user_permission_role.fields.assigned_by_user',
                'header_style' => 'width: 20%; text-align: center',
                'template'=> 'bundles/SonataAdminBundle/CRUD/list_assigned_by_user.html.twig'
           ])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->with('user_permission_role.show.group.general', [
                'label' => 'user_permission_role.show.group.general',
                'translation_domain' => 'Permission',
            'box_class' => 'box box-solid box-solid-with'
                ])
            ->add('id', null, [
                'label' => 'user_permission_role.fields.id',
            'translation_domain' => 'Permission',
            ])
            ->add('roles', FieldDescriptionInterface::TYPE_MANY_TO_ONE, [
                'label' => 'user_permission_role.fields.permission_role',
                'route' => ['name' => 'show'],
            'translation_domain' => 'Permission',
            ])
            ->add('userType', FieldDescriptionInterface::TYPE_ENUM, [
                'label' => 'user_permission_role.fields.user_type',
            'template' => 'bundles/SonataAdminBundle/CRUD/show_user_class.html.twig',
            'translation_domain' => 'Permission',
            ])
            ->add('userId', null, [
                'label' => 'user_permission_role.fields.user_id',
                  'translation_domain' => 'Permission',
                
            ])
            ->add('assignedAt', FieldDescriptionInterface::TYPE_DATETIME, [
                'label' => 'user_permission_role.fields.assigned_at',
                'format' => 'EEEE d MMMM yyyy, HH:mm:ss',
            'translation_domain' => 'Permission',
            ])
            ->end()

            ->with('user_permission_role.show.group.details', [
                'label' => 'user_permission_role.show.group.details',
            'translation_domain' => 'Permission',
            'box_class' => 'box box-solid box-solid-with',
            ])
            ->add('scope', null, [
                'label' => 'user_permission_role.fields.scope',
                  'translation_domain' => 'Permission',

            ])
            ->add('assignedByUser', FieldDescriptionInterface::TYPE_IDENTIFIER, [
                'translation_domain' => 'Permission',
                'label' => 'user_permission_role.fields.assigned_by_user',
                'template' => 'bundles/SonataAdminBundle/CRUD/show_assigned_by_user.html.twig'
            ])
            ->end();
    }
    public function configureFormFields(FormMapper $form): void
    {
        $form 
            ->with(
                'user_permission',
                [
                    'label' => false,
                    'class' => 'col-md-6',
                'translation_domain' => 'Permission',
                'box_class'   => 'box box-solid box-solid-with'
                ]
            )
            /*->ifTrue($this->isGranted('ROLE_FOUNDER'))
                ->add('userId', ChoiceType::class, [
                    'choices' => $this->adminUserRepository
                            ->findByUserWithExcludRole(['ROLE_FOUNDER','ROLE_SUPER_ADMIN']),
                    ,
                    'choice_translation_domain'=>'user',
                    'translation_domain' => 'Permission',
                    'label' => 'forms.user_permission_user_list',
                    'placeholder' => 'forms.user_permission_user_list_placeholder',
                    'attr' => [
                        'data-user_permission_role-target' => 'userSelect',
                        'class' => 'form-select form-control select2'
                    ]
                ])
                
            ->ifEnd()*/
            ->end()
            ->with('roles',[
                    'label' => 'forms.user_permission_role',
                    'class' => 'col-md-6',
                     'translation_domain' => 'Permission',
                    'box_class'   => 'box box-solid box-solid-with',
                ])
                ->add('roles', EntityType::class, [
                        'class' => PermissionRole::class,
                        'choice_label' => 'name',
                        'choice_value'=>'id',
                        'query_builder' => function(PermissionRoleRepository $permissionRoleModel):QueryBuilder {
                            return $permissionRoleModel->getPermissionRoleCreatedByUserQueryBuilder($this->securityContextUser->getCurrentUser()) ;
                        },
                        'translation_domain' => 'Permission',
                        'label' => false,
                        'by_reference' => true,
                        'attr' => [
                            'placeholder' => 'forms.user_permission_role_placeholder',
                            'class' => 'form-select form-control select2'
                        ]
                    ])
                    ->add('scope', TextType::class, [
                        'required' => false,
                        'label' => 'forms.user_permission_scope',
                        'label_attr' => ['class' => 'mt-3'],
                        'translation_domain' => 'Permission',
                        'attr' => [
                            'placeholder' => 'forms.user_permission_scope_placeholder',
                            'minlength' => 3,
                            'maxlength' => 100,
                            'data-pattern' => '^[\p{L}\p{M}=\/_\s\']+$',
                            'data-eg-await' => 'Domaine = Finance',
                            'data-escapestrip-html-and-php-tags' => 'true',
                            'data-event-validate-blur' => 'blur',
                            'data-event-validate-input' => 'input',
                            'data-flag-pattern' => 'iu',
                        ] 
                    ])
            ->end()
            ;
    }

    
    public function toString(object $object): string
    {
        $user_id= (string) $object->getUserId();
        return \sprintf('  %s ',$user_id);
    }
}

