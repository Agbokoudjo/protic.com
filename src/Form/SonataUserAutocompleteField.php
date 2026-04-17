<?php
// src/Form/AuthorAutocompleteField.php
namespace App\Form;

use App\Entity\SonataUser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class SonataUserAutocompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => SonataUser::class,
            'placeholder' => 'Rechercher un utilisateur...',
            'choice_label' => 'fullName', // ou la méthode qui affiche le nom complet
            'choice_value' => 'id',
            'searchable_fields' => ['fullName'],
            'min_characters' => 4,
            'preload' => false,
            // Sécurité : Optionnel, pour restreindre l'accès à l'API de recherche
            'security' => 'ROLE_ADMIN',

            'attr' => [
                'class' => 'form-select-lg', // Pour garder ton style actuel
            ],
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
