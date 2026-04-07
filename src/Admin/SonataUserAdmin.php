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

use App\Entity\SonataUser;
use App\Service\CanonicalFieldsUpdaterInterface;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\BooleanFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateTimeFilter;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Administration des utilisateurs Sonata (back-office ProTIC).
 *
 * Règles métier :
 *  - Tout utilisateur créé via ce formulaire est automatiquement activé (enabled = true).
 *  - Les utilisateurs créés via fixtures sont désactivés par défaut.
 *  - Le mot de passe est hashé dans prePersist / preUpdate.
 *  - La suppression physique est désactivée (soft-delete via Gedmo).
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 */
#[AutoconfigureTag(
    name: 'sonata.admin',
    attributes: [
        'id'           => 'app.admin.sonata_user',
        'code'         => 'app.admin.sonata_user',
        'admin_code'   => 'app.admin.sonata_user',
        'model_class'  => SonataUser::class,
        'manager_type' => 'orm',
        'group'        => 'app.admin.group.user',
        'label'        => 'Utilisateurs',
        'pager_type'   => 'simple',
    ]
)]
final class SonataUserAdmin extends WlindablaAdmin
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private CanonicalFieldsUpdaterInterface $canonicalFieldsUpdater
    ) {
        parent::__construct(
            'Liste des utilisateurs',
            'Détails de l\'utilisateur',
            'Créer un utilisateur',
            'Modifier l\'utilisateur',
        );
    }

    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'sonata_admin_user';
    }

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'admin_user';
    }

    /**
     * Pas de suppression physique — le soft-delete Gedmo suffit.
     */
    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('delete');
    }

    // ─────────────────────────────────────────────
    //  HOOKS
    // ─────────────────────────────────────────────

    /**
     * Avant la persistance :
     *  - Active automatiquement le compte (créé via l'admin = compte légitime).
     *  - Génère usernameCanonical / emailCanonical.
     *  - Hashe le mot de passe saisi dans le formulaire.
     */
    protected function prePersist(object $object): void
    {
        /** @var SonataUser $object */
        $object->setEnabled(true);
        $object->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        $this->canonicalFieldsUpdater->updateCanonicalFields($object);
        $plainPassword = $object->getPlainPassword();
        if ($plainPassword) {
            $hashed = $this->passwordHasher->hashPassword($object, $plainPassword);
            $object->setPassword($hashed);
            $object->eraseCredentials();
        }
    }
    protected function preValidate(object $object): void
    {
        $object->setRoles(['ROLE_ADMIN','ROLE_ASSISTANCE']) ;
    }

    /**
     * Avant la mise à jour :
     *  - Met à jour updatedAt.
     *  - Re-hashe le mot de passe si un nouveau a été saisi.
     */
    protected function preUpdate(object $object): void
    {
        /** @var SonataUser $object */
        $object->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $this->canonicalFieldsUpdater->updateCanonicalFields($object);

        $plainPassword = $object->getPlainPassword();
        if ($plainPassword) {
            $hashed = $this->passwordHasher->hashPassword($object, $plainPassword);
            $object->setPassword($hashed);
            $object->eraseCredentials();
        }
    }

    // ─────────────────────────────────────────────
    //  FORMULAIRE
    // ─────────────────────────────────────────────

    protected function configureFormFields(FormMapper $form): void
    {
        $isEdit = ($this->getSubject()?->getId() !== null);

        $form
            ->with('Identité', ['class' => 'col-md-6'])
            ->add('username', TextType::class, [
                'label'    => 'Nom d\'utilisateur',
                'attr'     => ['placeholder' => 'ex: franck_admin'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr'  => ['placeholder' => 'email@proticeditions.com'],
            ])
            ->add('profile', TextType::class, [
                'label'    => 'Nom complet / Profil',
                'required' => false,
                'attr'     => ['placeholder' => 'ex: Franck AGBOKOUDJO'],
            ])
            ->end()
            ->with('Sécurité', ['class' => 'col-md-6'])
            ->add('plainPassword', RepeatedType::class, [
                'type'            => PasswordType::class,
                'required'        => !$isEdit,
                'first_options'   => [
                    'label' => 'Mot de passe',
                    'attr'  => ['autocomplete' => 'new-password'],
                ],
                'second_options'  => [
                    'label' => 'Confirmer le mot de passe',
                    'attr'  => ['autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'mapped'          => false,
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
            /*->add('roles', ChoiceType::class, [
                'label'    => 'Rôles',
                'choices'  => [
                    'Administrateur'       => 'ROLE_ADMIN',
                    'Super Administrateur' => 'ROLE_SUPER_ADMIN',
                    'Éditeur'              => 'ROLE_EDITOR',
                ],
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('enabled', CheckboxType::class, [
                'label'    => 'Compte actif',
                'required' => false,
            ])*/
            ->end();
    }

    // ─────────────────────────────────────────────
    //  FILTRES
    // ─────────────────────────────────────────────

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('username', StringFilter::class, [
                'label'                   => 'Nom d\'utilisateur',
                'field_type'              => TextType::class,
                'force_case_insensitivity' => true,
                'field_options'           => ['attr' => ['placeholder' => 'Rechercher…']],
                'show_filter'             => true,
            ])
            ->add('email', StringFilter::class, [
                'label'                   => 'Email',
                'field_type'              => TextType::class,
                'force_case_insensitivity' => true,
                'show_filter'             => true,
            ])
            // ->add('enabled', BooleanFilter::class, [
            //     'label'       => 'Compte actif ?',
            //     'show_filter' => true,
            // ])
            ->add('createdAt', DateTimeFilter::class, [
                'label'         => 'Date de création',
                'field_type'    => DateTimeType::class,
                'field_options' => ['widget' => 'single_text'],
            ]);
    }

    // ─────────────────────────────────────────────
    //  LISTE
    // ─────────────────────────────────────────────

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('id', null, ['label' => '#', 'sortable' => true])
            ->add('username', null, ['label' => 'Nom d\'utilisateur', 'sortable' => true])
            ->add('email', null, ['label' => 'Email'])
            ->add('profile', null, ['label' => 'Profil'])
            // ->add('roles', null, [
            //     'label'    => 'Rôles',
            //     'template' => 'bundles/SonataAdminBundle/CRUD/list_roles.html.twig',
            // ])
            ->add('enabled', 'boolean', [
                'label'    => 'Actif',
                'editable' => true,
                'sortable' => true,
            ])
            ->add('lastLogin', null, [
                'label'  => 'Dernière connexion',
                'format' => 'd/m/Y H:i',
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
                ],
            ]);
    }

    // ─────────────────────────────────────────────
    //  SHOW
    // ─────────────────────────────────────────────

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->with('Identité', ['class' => 'col-md-6'])
            ->add('id', null, ['label' => 'ID'])
            ->add('username', null, ['label' => 'Nom d\'utilisateur'])
            ->add('email', FieldDescriptionInterface::TYPE_EMAIL, ['label' => 'Email'])
            ->add('profile', null, ['label' => 'Profil / Nom complet'])
            ->add('slug', null, ['label' => 'Slug'])
            ->end()
            ->with('Statut & Sécurité', ['class' => 'col-md-6'])
            ->add('enabled', FieldDescriptionInterface::TYPE_BOOLEAN, ['label' => 'Compte actif'])
            ->add('emailVerified', FieldDescriptionInterface::TYPE_BOOLEAN, ['label' => 'Email vérifié'])
            // ->add('roles', FieldDescriptionInterface::TYPE_ARRAY, ['label' => 'Rôles'])
            ->add('lastLogin', FieldDescriptionInterface::TYPE_DATETIME, [
                'label'  => 'Dernière connexion',
                'format' => 'd/m/Y à H:i',
            ])
            ->add('createdAt', FieldDescriptionInterface::TYPE_DATETIME, [
                'label'  => 'Créé le',
                'format' => 'd/m/Y à H:i',
            ])
            ->add('updatedAt', FieldDescriptionInterface::TYPE_DATETIME, [
                'label'  => 'Modifié le',
                'format' => 'd/m/Y à H:i',
            ])
            ->end();
    }
}
