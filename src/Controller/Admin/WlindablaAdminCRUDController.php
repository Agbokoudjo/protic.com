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

namespace App\Controller\Admin;

use App\CommandHandler\GenerateTemporaryPasswordHandler;
use App\CommandHandler\ToggleUserAccountHandler;
use App\Entity\BaseUserInterface;
use App\Exception\InvalidTokenException;
use App\Persistance\UserManagerInterface;
use App\Queue\AsyncMethodDispatcherInterface;
use App\Security\EmailVerificationInterface;
use App\Service\AccountStatus;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Sonata\AdminBundle\Exception\ModelManagerThrowable;
use App\Service\ProcessingErrorFormHandle;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
class WlindablaAdminCRUDController extends CRUDController {

    public static function getSubscribedServices(): array
    {
        return [
            // On ajoute votre service personnalisé ici
            ProcessingErrorFormHandle::class => ProcessingErrorFormHandle::class,
        ] + parent::getSubscribedServices();
    }

    /**
     * Active ou désactive un compte utilisateur.
     * 
     * Le UserType est automatiquement résolu et décodé par UserTypeArgumentValueResolver.
     * Pas besoin de le décoder manuellement.
     *
     * @throws BadRequestHttpException Si les données ne sont pas valides
     */
    public function toggleEnabledUserAccountAction(
        Request $request,
        string|int $id,
        UserManagerInterface $userManager,
        AsyncMethodDispatcherInterface $dispatchMessage
    ): JsonResponse {
        
        if (!$this->isXmlHttpRequest($request) || !$request->isMethod('PATCH')) {
            return $this->createFlashJsonResponse(
                type:"danger",
                title: 'Error',
                message: 'Accès non autorisé. Veuillez réessayer.',
                statusCode: Response::HTTP_BAD_REQUEST
            );
        }

        try {
            // Validation du slug
            if (!$id || empty(trim($id))) {
                throw new BadRequestHttpException('L\'identifiant de l\'utilisateur est manquant.');
            }

            /** @var BaseUserInterface|null */
            $user = $userManager->find($id);

            if (!($user instanceof BaseUserInterface)) {
                return $this->createFlashJsonResponse(
                    type:"warning",
                    title: 'Warning',
                    message: sprintf('❌ Aucun utilisateur trouvé avec l\'identifiant "%s".', $id),
                    statusCode: Response::HTTP_NOT_FOUND
                );
            }

            if (!($user->isEmailVerified())) {
                return $this->json([
                        'title'=>'Action Bloquée : Email Non Vérifié',
                        'message'=>\sprintf(
                            'L\'adresse Email "%s" associée au compte "%s" n\'a pas été vérifiée. Cette action est donc bloquée. Veuillez cliquer sur le bouton "Renvoyer l\'email de vérification" pour procéder.',
                            $user->getEmail(),
                            $user->getUsername() 
                        )
                        ],
                    Response::HTTP_FORBIDDEN
                );
            }
            
            // Récupérer et valider le nouveau statut
            $isEnabled = $this->getStatusFromRequest($request);
            $accountStatus = $isEnabled ? AccountStatus::ACTIVE : AccountStatus::INACTIVE;
           
            $user->setEnabled($isEnabled);

            $this->dispatchToggleUserAccountTask($user,$accountStatus, $dispatchMessage);

            $message = $this->buildSuccessMessage($user, $isEnabled);

            return $this->createFlashJsonResponse(
                 type:"success",
                title: 'Opération réussie',
                message: $message,
                statusCode: Response::HTTP_OK
            );
        } catch (BadRequestHttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            return $this->createFlashJsonResponse(
                type:"danger",
                title: 'Error',
                message: 'Une erreur est survenue lors de la mise à jour du statut.Veuillez actualiser et réessayer.',
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function resendEmailVerifyAction(
        Request $request,
        string |int $id,
        EmailVerificationInterface $verificationService
    ):JsonResponse{

        if (!$this->isXmlHttpRequest($request) || !$request->isMethod('GET')) {
            return $this->createFlashJsonResponse(
                type: "danger",
                title: 'Error',
                message: 'Accès non autorisé. Veuillez réessayer.',
                statusCode: Response::HTTP_BAD_REQUEST
            );
        }

        try{
            // Validation du slug
            if (!$id || empty(trim($id))) {
                throw new BadRequestHttpException('LL\'identifiant de l\'utilisateur est manquant.');
            }

            $verificationService->resendVerificationEmail($id);
            $type = "success";
            $title = "Email de Vérification Envoyé !";
            $message = \sprintf(
                "Le lien de vérification a été renvoyé avec succès. Veuillez informer l'utilisateur de consulter sa boîte de réception ou ses spams pour confirmer son compte.",
            );
            $statusHttp = Response::HTTP_OK;
        }catch(BadRequestHttpException |InvalidTokenException |\RuntimeException|\Exception $e){
            $type = "danger";
            $title = "Échec de l'Envoi"; 
            $message = $e->getMessage();
            $statusHttp = Response::HTTP_BAD_REQUEST;
        }

        return $this->createFlashJsonResponse(
            type: $type,
            title: $title,
            message: $message,
            statusCode: $statusHttp
        );
    }

    public function regenerateTemporyPasswordUserAction(
        Request $request,
        int|string $id,
        UserManagerInterface $userManager,
        AsyncMethodDispatcherInterface $dispatchMessage
       ):JsonResponse{

        if (!$this->isXmlHttpRequest($request) || !$request->isMethod('PATCH')) {
            return $this->createFlashJsonResponse(
                type: "danger",
                title: 'Error',
                message: 'Requête non autorisée. Veuillez utiliser le canal PATCH/AJAX approprié.',
                statusCode: Response::HTTP_BAD_REQUEST
            );
        }

         // Validation du id
        if (!$id || empty(trim($id))) {
            return $this->createFlashJsonResponse(
                type: "error",
                title: 'Donnée Manquante',
                message: 'L\'identifiant utilisateur (ID) est requis et ne peut être vide.',
                statusCode: Response::HTTP_BAD_REQUEST
            );
        }

        /** @var BaseUserInterface|null */
        $user = $userManager->find($id);

        if (!($user instanceof BaseUserInterface)) {
            return $this->createFlashJsonResponse(
                type: "warning",
                title: 'Warning',
                message: sprintf('❌ Aucun utilisateur trouvé avec l\'identifiant "%s".', $id),
                statusCode: Response::HTTP_NOT_FOUND
            );
        }
        
        if (!($user->isEmailVerified())) {
                return $this->json([
                        'title'=>'Action Bloquée : Email Non Vérifié',
                        'message'=>\sprintf(
                            'L\'adresse Email "%s" associée au compte "%s" n\'a pas été vérifiée. Cette action est donc bloquée. Veuillez cliquer sur le bouton "Renvoyer l\'email de vérification" pour procéder.',
                            $user->getEmail(),
                            $user->getUsername() 
                        )
                        ],
                    Response::HTTP_FORBIDDEN
                );
            }

        try {
           $dispatchMessage->dispatch(
                GenerateTemporaryPasswordHandler::class,
                'process',
                [$id]
            );

            $type = "success";
            $title = "Mot de Passe Temporaire Généré et Envoyé !";
            $message = "Un nouveau mot de passe temporaire a été généré avec succès. Une notification a été immédiatement envoyée à la boîte e-mail de l'utilisateur pour qu'il puisse se connecter et le modifier.";
            $statusHttp = Response::HTTP_OK;
        } catch (\Exception $e) {
            $type = "danger";
            $title = "Échec de la Réinitialisation";
            $message = "Une erreur est survenue lors du processus de régeneration du mot de passe : " . $e->getMessage();
            $statusHttp = Response::HTTP_BAD_REQUEST;
        }

        return $this->createFlashJsonResponse(
            type: $type,
            title: $title,
            message: $message,
            statusCode: $statusHttp
        );
    }

    /**
     * Construit le message de succès percutant
     */
    private function buildSuccessMessage(BaseUserInterface $user, bool $isEnabled): string
    {
        $action = $isEnabled
            ? 'Compte activé avec succès'
            : 'Compte désactivé avec succès';

        return sprintf(
            '%s - %s (ID: %s)',
            $action,
            $user->getUsername(),
            $user->getId()
        );
    }

    /**
     * Dispatch la tâche async de basculement de compte
     */
    private function dispatchToggleUserAccountTask(
        BaseUserInterface $user,
        AccountStatus $accountStatus,
        AsyncMethodDispatcherInterface $dispatchMessage
    ): void {
        $dispatchMessage->dispatch(
            ToggleUserAccountHandler::class,
            'handle',
            [$user->getId(), $accountStatus]
        );
    } 

    /**
     * Crée une réponse JSON avec flash message
     */
    final protected function createFlashJsonResponse(
        string  $type,
        string $title,
        string $message,
        int $statusCode = Response::HTTP_OK
    ): JsonResponse {
        return $this->json(
            [
                'title' => $title,
                'type' => $type,
                'message' => $message,
            ],
            $statusCode
        );
    }

    /**
     * Récupère et valide le statut de la requête
     * 
     * @throws BadRequestHttpException Si le statut est invalide
     */
    protected function getStatusFromRequest(Request $request): bool{

        $status=$request->getPayload()->getBoolean('status');

        if ($status === null) {
            throw new BadRequestHttpException('Le paramètre "status" est manquant.');
        }

        if (is_bool($status)) { return $status;}

        if (is_string($status)) {
            $status = strtolower($status);

            if (in_array($status, ['true', '1', 'on', 'yes'], true)) {
                return true;
            }

            if (in_array($status, ['false', '0', 'off', 'no'], true)) {
                return false;
            }

        }

        throw new BadRequestHttpException(
            sprintf('Statut invalide : "%s". Utiliser "true" ou "false".', $status)
        );
    }

    protected function handleXmlHttpRequestErrorResponse(Request $request, FormInterface $form): ?JsonResponse
    {
        if ([] === array_intersect(['application/json', '*/*'], $request->getAcceptableContentTypes())) {
            return $this->renderJson([], Response::HTTP_NOT_ACCEPTABLE);
        }

        /** @var ProcessingErrorFormHandle $formErrorHandle */
        $formErrorHandle = $this->container->get(ProcessingErrorFormHandle::class);

        return $this->json([
            'title' => 'Erreur de validation',
            'details' => 'Certaines informations sont invalides ou manquantes...',
            'violations' => $formErrorHandle->handle(
                $form,
                'validators',
                $request->getLocale()
            ),
            'formName' => $form->getName()
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    protected function handleXmlHttpRequestSuccessResponse(Request $request, object $object): JsonResponse
    {
        if ([] === array_intersect(['application/json', '*/*'], $request->getAcceptableContentTypes())) {
            return $this->renderJson([], Response::HTTP_NOT_ACCEPTABLE);
        }

        $message_trans_key= "flash_create_success" ;
        if($this->admin->isCurrentRoute('edit',$this->admin->getCode()) ||
            $request->attributes->get($this->admin->getIdParameter(),null) !==null
            ){
                $message_trans_key = "flash_edit_success";
            }

        $this->addFlash(
            'sonata_flash_success',
            $this->trans(
                $message_trans_key,
                ['%name%' => $this->escapeHtml($this->admin->toString($object))],
                'SonataAdminBundle'
            )
        );

        $message=$this->renderView('bundles/SonataTwigBundle/FlashMessage/render.html.twig') ;
        return $this->json([
            'result' => 'ok',
            'objectId' => $this->admin->getNormalizedIdentifier($object),
            'message' => $message,
        ],200);
    }

    /**
     * Appelé avant listAction().
     * On configure la requête pour notre mode SPA :
     * - Pas de colonne "Sélectionner"
     * - Pas de colonne "Batch" (checkbox de sélection multiple)
     */
    protected function preList(Request $request): ?Response
    {
        if ($request->isXmlHttpRequest()) {
            //Désactiver la colonne "Sélectionner"
            $request->query->set('select', false);

            //Désactiver les checkboxes batch dans le header tableau
            // (les batch actions dans le footer restent gérées par {% if not app.request.isXmlHttpRequest %})
            $request->query->set('batch', false);
        }

        return null;
    }
    
    
        /**
     * Execute a batch delete with XmlHttpRequest support for the SPA.
     *
     * Extends Sonata's default batchActionDelete() to return a JSON response
     * when the request is an XmlHttpRequest (sent by DeletePageSubscriber).
     *
     * Pipeline:
     *   - Standard POST (non-XHR) → comportement Sonata original (redirectToList)
     *   - XHR POST                → JSON response so the SPA can handle navigation itself
     *
     * JSON success response:
     *   { "result": "ok", "message": "Items successfully deleted." }
     *
     * JSON error response:
     *   { "result": "error", "message": "<error detail>" }
     *
     * The SPA (DeletePageSubscriber) reads "result" to decide whether to dispatch
     * SpaDeleteSucceededEvent or SpaDeleteFailedEvent.
     *
     * @throws AccessDeniedException If access is not granted
     *
     * @phpstan-param ProxyQueryInterface<T> $query
     */
    public function batchActionDelete(ProxyQueryInterface $query): Response
    {
        $this->admin->checkAccess('batchDelete');
        $request=$this->admin->getRequest() ;
        $modelManager = $this->admin->getModelManager();

        try {
            $modelManager->batchDelete($this->admin->getClass(), $query);

            // ── XHR path → JSON for the SPA ──────────────────────────────────
            if ($this->isXmlHttpRequest($request)) {
                return $this->renderJson([
                    'result'  => 'ok',
                    'message' => $this->trans('flash_batch_delete_success', [], 'SonataAdminBundle'),
                ]);
            }

            // ── Standard path → flash + redirect (comportement Sonata original) ─
            $this->addFlash(
                'sonata_flash_success',
                $this->trans('flash_batch_delete_success', [], 'SonataAdminBundle')
            );

        } catch (ModelManagerException $e) {
            // NEXT_MAJOR: Remove this catch.
            $errorMessage = $this->handleModelManagerException($e);

            if ($this->isXmlHttpRequest($request)) {
                return $this->renderJson([
                    'result'  => 'error',
                    'message' => $errorMessage ?? $this->trans('flash_batch_delete_error', [], 'SonataAdminBundle'),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $this->addFlash(
                'sonata_flash_error',
                $errorMessage ?? $this->trans('flash_batch_delete_error', [], 'SonataAdminBundle')
            );

        } catch (ModelManagerThrowable $e) {
            $errorMessage = $this->handleModelManagerThrowable($e);

            if ($this->isXmlHttpRequest($request)) {
                return $this->renderJson([
                    'result'  => 'error',
                    'message' => $errorMessage ?? $this->trans('flash_batch_delete_error', [], 'SonataAdminBundle'),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $this->addFlash(
                'sonata_flash_error',
                $errorMessage ?? $this->trans('flash_batch_delete_error', [], 'SonataAdminBundle')
            );
        }

        return $this->redirectToList();
    }
}
