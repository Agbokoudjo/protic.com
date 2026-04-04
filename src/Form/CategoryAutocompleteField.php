<?php

// src/Form/CategoryAutocompleteField.php
namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class CategoryAutocompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Category::class,
            'placeholder' => 'Rechercher une catégorie (ex: Roman, Sci-Fi...)',
            'choice_label' => 'name',
            'preload' => false,
            // On indexe la recherche sur le champ 'name'
            'searchable_fields' => ['name','slug'],

            // On peut définir le nombre de résultats maximum affichés
            'max_results' => 10,

            // On peut forcer le respect du minimum de caractères avant de lancer l'Ajax
            // pour correspondre à ton Assert\Length(min: 3)
            'min_characters' => 3,

            'attr' => [
                'class' => 'form-select-lg',
                'data-error-message' => 'Veuillez sélectionner une catégorie valide.',
            ],
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}