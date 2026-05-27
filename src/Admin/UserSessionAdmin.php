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
use App\Entity\UserSession;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Administration des sessions utilisateurs uniques.
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
#[AutoconfigureTag(
    name: 'sonata.admin',
    attributes: [
        'id' => 'sonata.admin.user.session',
        'code' => "sonata.admin.user.session",
        'admin_code' => "sonata.admin.user.session",
        'model_class' => UserSession::class,
        'manager_type' => 'orm',
        'group' => 'sonata.admin.logger',
        'group_code' => 'sonata.admin.logger',
        'label' => 'Sessions Utilisateurs',
        'label_code' => 'Sessions Utilisateurs',
        'show_in_roles_matrix' => true,
        'pager_type' => 'simple'
    ] 
)]
final class UserSessionAdmin extends WlindablaAdmin{

    public function __construct() {
        parent::__construct('Liste des sessions utilisateurs', 'user_session');
    }

    // protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    // {
    //     $qb = $query->getQueryBuilder();
    //     $em = $qb->getEntityManager();

    //     // Désactivation du filtre SoftDeleteable pour cette requête
    //     if ($em->getFilters()->isEnabled('soft-deleteable')) {
    //         $em->getFilters()->disable('soft-deleteable');
    //     }

    //     $rootAlias = current($qb->getRootAliases());

    //     // Optionnel : Si vous voulez UNIQUEMENT les éléments supprimés
    //     // $qb->andWhere($rootAlias . '.deletedAt IS NOT NULL');

    //     return $query;
    // }
    
    /**
     * On désactive la création manuelle car une session est générée par le système.
     */
    protected function configureRoutes(RouteCollectionInterface $collection): void
    { 
        $collection->remove('create');
        $collection->remove('edit');
        $collection->remove('delete');
    }

     protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('userIdentifier', null, [
                'label'              => 'Utilisateur',
                'translation_domain' => 'UserSession',
            ])
            ->add('ipAddress', null, [
                'label'              => 'Adresse IP',
                'translation_domain' => 'UserSession',
            ])
            ->add('sessionId', null, [
                'label'              => 'ID de session',
                'translation_domain' => 'UserSession',
            ])
            ->add('active', null, [
                'label'              => 'Active',
                'translation_domain' => 'UserSession',
            ]);
    }
    
     protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('userIdentifier', null, [
                'label'              => 'Utilisateur',
                'translation_domain' => 'UserSession',
            ])
            ->add('ipAddress', null, [
                'label'              => 'Adresse IP',
                'translation_domain' => 'UserSession',
            ])
            ->add('active', null, [
                'label'              => 'Active',
                'translation_domain' => 'UserSession',
            ])
            ->add('lastActivityAt', 'datetime', [    // ← lastActivityAt, pas lastActivity
                'label'              => 'Dernière activité',
                'format'             => 'd/m/Y H:i:s',
                'translation_domain' => 'UserSession',
            ])
            ->add('createdAt', 'datetime', [
                'label'              => 'Créée le',
                'format'             => 'd/m/Y H:i:s',
                'translation_domain' => 'UserSession',
            ])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions'            => ['show' => []],
                'label'              => 'Actions',
                'translation_domain' => 'UserSession',
            ]);
    }

    /**
     * Configuration de la vue détaillée (Show)
     */
    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->with('admin.user_session.group_general', 
                    ['class' => 'col-md-6',  
                    'class' => 'col-md-6',
                  'box_class' => 'box box-solid box-solid-with',
                    'translation_domain' => 'UserSession',])
                ->add('id', null, [
                    'label' => 'admin.user_session.id', 
                    'translation_domain' => 'UserSession'
                    ])
                ->add('userIdentifier', null, [
                    'label' => 'admin.user_session.user_identifier',
                     'translation_domain' => 'UserSession'])
                ->add('sessionId', null, [
                  'label' => 'admin.user_session.session_id',
                 'translation_domain' => 'UserSession',])
            ->end()
            ->with('admin.user_session.group_details', [
                    'class' => 'col-md-6', 
                    'box_class' => 'box box-solid box-solid-with',
                'translation_domain' => 'UserSession'])
                ->add('ipAddress', null, [
                    'label' => 'admin.user_session.ip_address',
                     'translation_domain' => 'UserSession'])
                ->add('userAgent', null, [
                    'label' => 'admin.user_session.user_agent',
                     'translation_domain' => 'UserSession'])
                ->add('createdAt', 'datetime', [
                    'label' => 'admin.user_session.created_at',
                     'translation_domain' => 'UserSession'])
                ->add('lastActivityAt', 'datetime', [
                    'label' => 'admin.user_session.last_activity',
                     'translation_domain' => 'UserSession'])
            ->end();
    }

    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'sonata_admin_user_session';
    }

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'user_session';
    }

    /**
     * Titre de la page de liste (dans le menu et le header)
     */
    public function toString(object $object): string
    {
        return $object instanceof UserSession
            ?  $object->getUserIdentifier()
            : 'Session Utilisateur';
    }
}
