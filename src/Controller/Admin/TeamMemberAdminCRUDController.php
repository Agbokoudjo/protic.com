<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\WlindablaAdminCRUDController;
use App\Entity\TeamMember;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contrôleur CRUD personnalisé pour TeamMember (ProTIC Editions & Services).
 *
 * Actions supplémentaires :
 *  - toggleVisibleTeamMemberAction → bascule visible ↔ masqué (PATCH).
 * Le contrôleur doit également être déclaré dans sonata_admin.yaml :
 *  sonata_admin:
 *      ...  (voir INTEGRATION.md section 6)
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 *
 * @extends WlindablaAdminCRUDController<TeamMember>
 */
final class TeamMemberAdminCRUDController extends WlindablaAdminCRUDController {

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    /**
     * Bascule la visibilité d'un membre de l'équipe : visible → masqué ou masqué → visible.
     *
     * Route     : PATCH  /admin/admin_team_member/toggle_visible/{id}
     *
     * Réponse selon le contexte :
     *  • Requête AJAX (XHR ou Accept: application/json)
     *    → JsonResponse avec les données du nouveau bouton pour mise à jour JS sans rechargement
     *  • Requête classique (navigateur)
     *    → Redirect vers la liste + flash message Sonata
     *
     * CSRF     : token attendu dans X-CSRF-TOKEN (header AJAX) ou _token (form body).
     *            Nom du token : 'toggle_visible_team_member_{id}'
     */
    public function toggleVisibleTeamMemberAction(int $id, Request $request): JsonResponse
    {
        if (!$this->isXmlHttpRequest($request) || !$request->isMethod('PATCH')) {
            return $this->createFlashJsonResponse(
                type: "danger",
                title: 'Error',
                message: 'Accès non autorisé. Veuillez réessayer.',
                statusCode: Response::HTTP_BAD_REQUEST
            );
        }

        /** @var TeamMember|null $teamMember */
        $teamMember = $this->entityManager
            ->getRepository(TeamMember::class)
            ->find($id);

        if (null === $teamMember) {
            return $this->handleNotFound($id);
        }

        $csrfToken = $request->headers->get('X-CSRF-TOKEN')
            ?? $request->request->get('_token')
            ?? '';

        $csrfTokenId = 'toggle_visible_team_member_' . $id;

        if (!$this->isCsrfTokenValid($csrfTokenId, $csrfToken)) {
            return $this->handleCsrfFailure($request);
        }

        $newVisibility = $this->getStatusFromRequest($request);

        $teamMember->setVisible($newVisibility);
        $teamMember->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));

        $this->entityManager->flush();

        $memberName = $teamMember->getName() ?? sprintf('Membre #%d', $id);

        $message   = $newVisibility
            ? sprintf('"%s" est maintenant visible sur le site ,actualisez la page pour voir les changements.', $memberName)
            : sprintf('"%s" est maintenant masqué sur le site,actualisez la page pour voir les changements.', $memberName);

        return $this->buildJsonResponse($id, $memberName, $newVisibility, $message);
    }

    /**
     * Construit la JsonResponse de succès avec les données nécessaires
     * pour que le template JS mette à jour le bouton sans rechargement de page.
     */
    private function buildJsonResponse(
        int    $id,
        string $memberName,
        bool   $newVisibility,
        string $message,
    ): JsonResponse {
        return new JsonResponse([
            'success'     => true,
            'visible'     => $newVisibility,
            'message'     => $message,
            'member_id'   => $id,
            'member_name' => $memberName,

            // Données complètes pour re-rendre le bouton côté JS
            'button' => [
                'label'     => $newVisibility ? 'Masquer'                       : 'Afficher',
                'icon'      => $newVisibility ? 'fas fa-eye-slash'              : 'fas fa-eye',
                'title'     => $newVisibility ? 'Masquer ce membre sur le site' : 'Rendre ce membre visible',
                'css_class' => $newVisibility ? 'btn btn-sm btn-warning'        : 'btn btn-sm btn-success',
                'confirm'   => $newVisibility
                    ? 'Voulez-vous masquer ce membre sur la page À propos ?'
                    : 'Voulez-vous afficher ce membre sur la page À propos ?',
            ],
        ]);
    }

    /**
     * Gère le cas "membre introuvable" (404).
     */
    private function handleNotFound(int $id): JsonResponse
    {
        return $this->json(
            [
                'message' => sprintf('Membre introuvable (id: %d).', $id),
                'title' => 'error'
            ],
            404
        );
    }

    /**
     * Gère l'échec de validation CSRF (498).
     */
    private function handleCsrfFailure(): JsonResponse
    {
        return $this->json([
            'message'=> 'Token CSRF invalide. Veuillez actualiser la page et réessayer.',
            'title'=>'error'
        ],
         498
        );
    }
}
