<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\ManuscriptSubmission;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ManuscriptSubmissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('fullName', TextType::class, [
                'label'    => false,
                'attr'     => [
                    'placeholder' => 'Ex : Kofi Adjovi', 
                    'autocomplete' => 'name',
                    'minlength' => 6,
                    'maxlength' => 255,
                    'data-pattern' => '^[\p{L}\p{N}\p{M}\s\-\.]+$',
                    'data-eg-await' => 'AGBOKOUDJO Franck',
                    'data-escapestrip-html-and-php-tags' => true,
                    'data-event-validate-blur' => 'blur',
                    'data-event-validate-input' => 'input',
                    'class' => 'username',
                    'data-position-lastname' => "left",
                    'data-error-message-input'          => 'Le nom ne peut contenir que des lettres, chiffres, espaces et tirets.',
                    ],
            ])
            ->add('email', EmailType::class, [
                'label'    => false,
                'attr'     => [
                    'placeholder' => 'votre@email.com', 
                    'autocomplete' => 'email',
                    'minlength' => 6,
                    'data-type' => "email",
                    'autocomplete'                      => 'email',
                    'maxlength'                         => 200,
                    'data-escapestrip-html-and-php-tags' => 'true',
                    'data-event-validate-blur'          => 'blur',
                    'data-event-validate-input'         => 'input',
                    'data-error-message-input'          => 'Veuillez saisir une adresse email valide.',
                    ],
            ])
            ->add('phone', PhoneNumberType::class, [
                'label'       => false,
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
            ->add('country', CountryType::class, [
                'label'  => false,
                'help'        => 'Pays d\'origine ou de résidence de l\'auteur.',
                'label_attr' => ['class' => 'form-label'],
                'required' => false,
                "alpha3" => true,
                'placeholder' => 'Sélectionnez un pays...',
                'choice_translation_domain' => false,
                'multiple' => false,
                'attr' => [
                    'data-escapestrip-html-and-php-tags' => true,
                    'data-event-validate-change' => 'change',
                    'data-event-validate-blur' => 'blur',
                    'class' => 'form-control pc-input pc-select select2 form-select-lg form-control-lg',
                    'data-minimumInputLength' => 1,
                    'data-error-message-input' => 'Veuillez sélectionner un pays.',
                    'data-sonata-select2-maximumSelectionLength' => 1,
                ]
            ])
            ->add('subject', TextType::class, [
                'label'       => false,
            'attr' => [
                'placeholder' => 'Soumission de manuscrit — Roman',
                'autocomplete' => 'on', 
                'minlength' => 10,
                'maxlength' => 255,
                'data-eg-await' => 'Soumission de manuscrit — Roman',
                'data-escapestrip-html-and-php-tags' => true,
                'data-event-validate-blur' => 'blur',
                'data-event-validate-input' => 'input',
                'data-pattern' => "^[\p{L}\p{N}\p{M}\s\-\.]+$" // Regex unicode compatible
            ]
            ])
            ->add('message', TextareaType::class, [
                'label' => false,
                'attr'  => [
                'placeholder' => 'Décrivez votre projet : genre, nombre de pages approximatif, vos attentes...',
                'rows'        => 6,
                'id'          => 'pc-message',
                'autocomplete' => 'on', 
                'minlength' => 20,
                'maxlength' => 4000,
                'data-escapestrip-html-and-php-tags' => 'true', // custom attribute (JS)
                'data-event-validate-blur' => 'blur',
                'data-event-validate-input' => 'input',
                'data-pattern' => "^[\p{L}\p{M}\p{N}\s\p{P}]+$",
                'data-error-message-input'           => 'Le message ne peut pas contenir de balises HTML. Entre 20 et 4000 caractères.'
            ],
            ])
            ->add('manuscriptFile', FileType::class, [
                'label'    => false,
                'required' => false,
                'attr'     => [
                    'accept' => '.pdf,.doc,.docx,.odt,.txt',
                    'class'  => 'pc-upload-input',
                    'data-event-validate-blur' => 'blur',
                    'data-event-validate-change' => 'change',
                    'data-event-validate-dragenter' => 'dragenter',
                    'data-event-validate-drop' => 'drop',
                    'data-media-type' => 'document', 
                    'data-type' => 'file',
                    'data-extentions' => 'pdf,doc,docx,odt,txt',
                    'data-unity-max-size-file' => 'MiB',
                    'data-maxsize-file' => 10,
                    'data-allowed-mime-type-accept' => '
                            application/pdf,
                           application/vnd.openxmlformats-officedocument.wordprocessingml.document,
                            application/msword,
                            application/vnd.oasis.opendocument.text,
                            text/plain,
                            application/x-pdf
                            ',
                'data-error-message-input'=> 'Le type de fichier n\'est pas valide. Les formats de manuscrits acceptés sont PDF, Word, ODT et TXT.'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'      => ManuscriptSubmission::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'manuscript_submission',
        ]);
    }
}
