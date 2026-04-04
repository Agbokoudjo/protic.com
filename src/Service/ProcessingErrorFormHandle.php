<?php

namespace App\Service;

use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProcessingErrorFormHandle
{
    public function __construct(private readonly TranslatorInterface $translator) {}

    public function handle(
        FormInterface $dataform, 
        ?string $domain = null, 
        ?string $locales = null
        ): array{

        return $this->getErrorMessages($dataform, $domain, $locales);
    }

    private function getErrorMessages(
        FormInterface $form,
        ?string $domain = null,
        ?string $locales = null,
        ?string  $parent = ""
    ): array {
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $fieldName = $parent ?: $form->getName();

            // Ajouter plusieurs erreurs dans un tableau
            if (!isset($errors[$fieldName])) {
                $errors[$fieldName] = [];
            }
            $errors[$fieldName][] = $this->translator->trans(
                $error->getMessage(),
                $error->getMessageParameters(),
                $domain,
                $locales
            );
        }
        // 2. Gérer les enfants (récursion)
        foreach ($form->all() as $child) {
            // Uniquement si l'enfant a des erreurs ou est un sous-formulaire qui pourrait contenir des erreurs
            if (!$child->isValid() || $child->count() > 0) { // $child->count() > 0 pour les sous-formulaires vides sans erreurs directes
                $childName = $child->getName();
                $fullName = $parent ? $parent . '.' . $childName : $childName;
                $childErrors = $this->getErrorMessages($child, $domain, $locales, $fullName); // Appel récursif

                if (!empty($childErrors)) {
                    // Fusionner proprement les erreurs
                    foreach ($childErrors as $key => $messages) {
                        if (!isset($errors[$key])) {
                            $errors[$key] = [];
                        }

                        $errors[$key] = array_merge($errors[$key], $messages);
                    }
                }
            }
        }

        return $errors;
    }
}
