<?php declare(strict_types=1);

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
use App\Entity\SonataUser;
use App\Form\SonataUserAutocompleteField;
use App\Repository\PermissionRoleRepository;
use App\Security\Handler\PermissionRoleSecurityHandler;
use App\Security\SecurityContextInterface;
use App\Validator\NotReservedRole;
use App\Validator\ReservedRoles;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\ModelFilter;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 * @inheritDoc Définition des rôles disponibles (ex : « Manager de projet », « Modérateur »).
 */
#[AutoconfigureTag(
    name: 'sonata.admin',
    attributes: [
        'id' => 'sonata.admin.permission.role',
        'code' => 'sonata.admin.permission.role',
        'admin_code' => 'sonata.admin.permission.role',
        'model_class' => PermissionRole::class,
        'manager_type' => 'orm',
        'group' => 'app.admin.group.permission.manager',
        'group_code' => 'app.admin.group.permission.role.manager',
        'label' => 'Rôles et Permissions',
        'show_in_roles_matrix' => true,
        'roles' => ['ROLE_ADMIN'],
        //'security_handler' => PermissionRoleSecurityHandler::class,
        'pager_type' => 'simple'
    ]
)]
final class PermissionRoleAdmin extends WlindablaAdmin
{
    public function __construct(
        private readonly SecurityContextInterface $securityContext,
        private readonly PermissionRoleRepository $permissionRoleRepo
    ) {
        parent::__construct('permission_role', 'permission_role', 'permission_role');
    }

    protected function prePersist(object $object): void
    {
        if (!$object instanceof PermissionRole) {
            return;
        }

        if ($currentUser = $this->securityContext->getCurrentUser()) {

            $object->setCreatedBy($currentUser);
        }

        $object->setCreatedAt(new \DateTimeImmutable('now',new  \DateTimeZone('UTC')));
    }

    protected function preUpdate(object $object): void
    {
        if (!$object instanceof PermissionRole) {
            return;
        }

        $object->setUpdatedAt(new \DateTime('now', new  \DateTimeZone('UTC')));
    }

    protected function postUpdate(object $object): void
    {
        if (!$object instanceof PermissionRole) {
            return;
        }

        if ($currentUser = $this->securityContext->getCurrentUser()) {

            $this->permissionRoleRepo->clearCacheUser($currentUser);
        }
    }

    protected function postPersist(object $object): void
    {
        if (!$object instanceof PermissionRole) {
            return;
        }

        if ($currentUser = $this->securityContext->getCurrentUser()) {

            $this->permissionRoleRepo->clearCacheUser($currentUser);
        }
    }

    protected function configureDatagridFilters(DatagridMapper $datagrid): void
    {
        $datagrid
            ->add('name',StringFilter::class,[
                'label' => 'list.permission_role_name',
                'translation_domain' => 'Permission',
                'force_case_insensitivity' => true,
                'field_type' => TextType::class,
                    'field_options' => [
                        'attr' => [
                            'placeholder' => 'filter.name',
                        ]
                    ],
                ])  
             ->add('context',StringFilter::class,[
                'label' => 'list.permission_role_context',
                'translation_domain' => 'Permission',
            'force_case_insensitivity' => true,
            'field_type' => TextType::class,
                'field_options' => [
                    'attr' => [
                        'placeholder' => 'filter.context',
                    ]
                ],
            ])
            ->add('createdBy', ModelFilter::class, [
                'label' => 'list.permission_role_createdBy',
                'translation_domain' => 'Permission',
                'field_type' => SonataUserAutocompleteField::class,
            'field_options' => [
                'class' => SonataUser::class,
                'autocomplete' => true,
                'attr' => [
                    'placeholder' => 'Filtrer par responsables de la creation de la permission'
                ]
            ]
                ])
            ;
    }

    protected function configureShowFields(ShowMapper $show):void{

        $show
            ->add('name', FieldDescriptionInterface::TYPE_STRING, [
                'label' => 'list.permission_role_name',
                'translation_domain' => 'Permission'
            ])
            ->add('context', FieldDescriptionInterface::TYPE_STRING, [
                'label' => 'list.permission_role_context',
                'translation_domain' => 'Permission'
            ])
            ->add('description', FieldDescriptionInterface::TYPE_TEXTAREA, [
                'label' => 'list.permission_role_description',
                'translation_domain' => 'Permission',
            ])
            ->add('createdBy',FieldDescriptionInterface::TYPE_MANY_TO_ONE,[
                'label' => 'list.permission_role_createdBy',
                'translation_domain' => 'Permission',
            'associated_property'=>'username'
            ])
            ->add('createdAt', FieldDescriptionInterface::TYPE_DATE, [
                'label' => 'createdAt',
                'translation_domain' => 'date',
                'inline' => true,
                'display' => 'both',
                'format' => 'd/m/Y H:i:s',
            ])

            ->add('updatedAt', FieldDescriptionInterface::TYPE_DATE, [
                'label' => 'updatedAt',
                'translation_domain' => 'date',
                'inline' => true,
                'display' => 'both',
                'format' => 'd/m/Y H:i:s',
            ])
            ;
    }

    public function configureListFields(ListMapper $list): void
    {
        $list
            ->add('name', FieldDescriptionInterface::TYPE_STRING, [
                'label' => 'list.permission_role_name',
            'translation_domain' => 'Permission'
            ])
            ->add('context', FieldDescriptionInterface::TYPE_STRING, [
                'label' => 'list.permission_role_context',
            'translation_domain' => 'Permission'
            ])
            ->add('description', FieldDescriptionInterface::TYPE_TEXTAREA, [
                'label' => 'list.permission_role_description',
                'translation_domain' => 'Permission',
                'template'=> 'bundles/SonataAdminBundle/CRUD/list_textarea.html.twig'

            ])
            ->add('createdAt',FieldDescriptionInterface::TYPE_DATE,[
                'label' => 'createdAt',
                'translation_domain' => 'date',
            'inline' => true,
            'display' => 'both',
            'format' => 'd/m/Y H:i:s',
            ])

            ->add('updatedAt', FieldDescriptionInterface::TYPE_DATE, [
                'label' => 'updatedAt',
                'translation_domain' => 'date',
                'inline' => true,
                'display' => 'both',
                'format' => 'd/m/Y H:i:s',
            ])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'delete' => [],
                ]
            ])
            ;
    }

    public function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('inform',
                ['label' => 'permission_role_general',
                    'class' => 'col-md-5',
                    'box_class' => 'box box-solid box-solid-with',
                    'translation_domain' => 'Permission'])
            ->add('name', TextType::class, [
                'label' => 'forms.permission_role_name',
                'label_attr' => ['class' => 'form-label'],
                'attr' => [
                    'placeholder' => 'forms.permission_role_name_placeholder',
                    'autocomplete' => 'on',  
                    'minlength' => 5, 
                    'maxlength' => 100, 
                    'data-pattern' => '^[A-Z_]+$',
                    'pattern' => '^[A-Z_]+$',
                    'data-eg-await' => 'PROJECT_MANAGER',
                    'data-escapestrip-html-and-php-tags' => 'true',
                    'data-event-validate-blur' => 'blur',
                    'data-event-validate-input' => 'input',
                    'data-flag-pattern' => 'u',
                    'data-input-reserved-roles-validate' => 'true',
                    'data-reserved-roles' => json_encode(ReservedRoles::RESERVED_ROLE_USER),
                    'data-error-message-input' => 'Le nom de rôle que vous avez saisi est invalide et ne peut pas être créé.',
                ],
                'translation_domain' => 'Permission',
                'constraints' => [
                    new NotReservedRole()
                ]
            ], [])
            ->add('context', TextType::class, [
                'label' => 'forms.permission_role_context',
                'label_attr' => ['class' => 'form-label'],
                'row_attr' => ['class' => 'mt-3'],
                'attr' => [
                    'placeholder' => 'forms.permission_role_context_placeholder',
                    'autocomplete' => 'on',  
                    'minlength' => 5,  
                    'maxlength' => 200,  
                    'data-pattern' => '^[\p{L}\p{M}=\/_\s\']+$',
                    'data-eg-await' => 'Domaine = UI/UX Design',
                    'data-escapestrip-html-and-php-tags' => 'true',
                    'data-event-validate-blur' => 'blur',
                    'data-event-validate-input' => 'input',
                ],
                'translation_domain' => 'Permission'
            ], [])
            ->end()
            ->with(
                'Description',
                [
                    'label' => 'permission_role_description_general',
                    'class' => 'col-md-7',
                    'translation_domain' => 'Permission',
                    'box_class' => 'box box-solid box-solid-with'
                ]
            )
            ->add('description', TextareaType::class, [
                'label' => false,
                'label_attr' => ['class' => 'form-label'],
                'attr' => [
                    'placeholder' => 'forms.permission_role_description_placeholder',
                    'autocomplete' => 'on',  
                    'minlength' => 20,  
                    'maxlength' => 10000,  
                    'data-escapestrip-html-and-php-tags' => 'true',  
                    'data-event-validate-blur' => 'blur',
                    'data-event-validate-input' => 'input',
                    'data-pattern' => "^[\p{L}\p{M}\p{N}\s\p{P}\n\r]+$",  
                    'data-error-message-input' =>'Le contenue de la description est invalide',
                    'rows' => 6
                ],
                'translation_domain' => 'Permission'
            ], [])
            ->end();
    }

    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'sonata_admin_permission_role';
    }

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'permission_role';
    }
}
