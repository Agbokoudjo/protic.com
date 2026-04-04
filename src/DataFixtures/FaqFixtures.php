<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Faq;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class FaqFixtures extends Fixture
{
    private const FAQS = [
        /* ── Publication ── */
        [
            'question' => 'Comment soumettre mon manuscrit à ProTIC Editions ?',
            'answer'   => 'Vous pouvez soumettre votre manuscrit de deux façons : en ligne via notre formulaire de contact disponible sur notre site web, ou directement en personne à notre siège situé sur le Campus d\'Abomey-Calavi, K61-62. Notre équipe vous recontactera dans un délai de 48 heures ouvrées.',
            'category' => 'Publication',
            'position' => 1,
        ],
        [
            'question' => 'Quels genres littéraires acceptez-vous ?',
            'answer'   => 'ProTIC Editions accepte tous les genres littéraires : Roman, Poésie, Théâtre, Conte, Essai et ouvrages Didactiques. Nous sommes ouverts à tous les auteurs béninois et africains, qu\'ils soient au Bénin ou dans la diaspora.',
            'category' => 'Publication',
            'position' => 2,
        ],
        [
            'question' => 'Combien de temps prend le processus de publication ?',
            'answer'   => 'Le délai moyen de publication varie entre 4 et 8 semaines à compter de la validation finale du manuscrit. Ce délai inclut la conception graphique, la mise en page, les corrections, le dépôt légal et l\'impression. Nous vous tenons informé à chaque étape du processus.',
            'category' => 'Publication',
            'position' => 3,
        ],
        [
            'question' => 'Est-ce que ProTIC gère le dépôt légal de mon livre ?',
            'answer'   => 'Oui, absolument ! ProTIC Editions prend entièrement en charge toutes les formalités administratives, notamment le dépôt légal auprès de la Bibliothèque Nationale du Bénin. Cela fait partie intégrante de notre offre de service. Votre livre sera officiellement enregistré.',
            'category' => 'Publication',
            'position' => 4,
        ],

        /* ── Tarifs ── */
        [
            'question' => 'Quels sont vos tarifs de publication ?',
            'answer'   => 'Nos tarifs varient en fonction de la nature du projet (nombre de pages, format, quantité d\'impression, type de reliure). Nous vous invitons à nous soumettre votre projet via le formulaire de contact pour recevoir un devis personnalisé et gratuit dans les 48 heures.',
            'category' => 'Tarifs',
            'position' => 5,
        ],
        [
            'question' => 'Proposez-vous des facilités de paiement ?',
            'answer'   => 'Oui, nous proposons des arrangements de paiement adaptés à votre situation. Vous pouvez payer par Mobile Money (MTN Money, Moov Money), virement bancaire sur notre compte UBA (N° 506070006684) ou en espèces à notre siège. Contactez-nous pour discuter des modalités.',
            'category' => 'Tarifs',
            'position' => 6,
        ],

        /* ── Distribution ── */
        [
            'question' => 'Comment sont distribués les livres publiés par ProTIC ?',
            'answer'   => 'ProTIC Editions assure la distribution de vos livres à l\'échelle nationale au Bénin (librairies, institutions scolaires, bibliothèques) et propose également une distribution sous-régionale en Afrique de l\'Ouest. Nous sommes membres de l\'APPEL-Bénin, ce qui renforce notre réseau de distribution.',
            'category' => 'Distribution',
            'position' => 7,
        ],
        [
            'question' => 'Les auteurs de la diaspora peuvent-ils publier avec ProTIC ?',
            'answer'   => 'Absolument ! Nous accueillons avec enthousiasme les auteurs béninois et africains vivant à l\'étranger. Vous pouvez soumettre votre manuscrit entièrement en ligne. Toutes les démarches peuvent être effectuées à distance par email ou WhatsApp. Le livre sera publié et distribué au Bénin.',
            'category' => 'Distribution',
            'position' => 8,
        ],

        /* ── Droits d'auteur ── */
        [
            'question' => 'Est-ce que je conserve les droits d\'auteur sur mon œuvre ?',
            'answer'   => 'Oui, vous conservez intégralement vos droits d\'auteur. ProTIC Editions ne revendique aucun droit de propriété sur votre œuvre. Nous agissons en tant que prestataire de services éditoriaux. Votre nom figure en tant qu\'auteur sur la couverture et dans toutes les formalités légales.',
            'category' => 'Droits',
            'position' => 9,
        ],
        [
            'question' => 'Puis-je publier mon livre ailleurs après avoir travaillé avec ProTIC ?',
            'answer'   => 'Oui, tout à fait. Notre contrat de prestation de services n\'est pas un contrat d\'exclusivité éditoriale. Vous êtes libre de publier vos œuvres futures avec d\'autres maisons d\'édition. Nous vous encourageons cependant à nous recontacter — nous serons toujours ravis de collaborer à nouveau !',
            'category' => 'Droits',
            'position' => 10,
        ],

        /* ── Général ── */
        [
            'question' => 'ProTIC Editions est-elle une entreprise officielle ?',
            'answer'   => 'Oui, ProTIC Editions & Services est une entreprise officiellement enregistrée au Bénin. Nous disposons d\'un RCCM (N° RB/ABC/21 A 32987), d\'un IFU (N° 0202112604781) et d\'un numéro CNSS (N° 21312177). Nous sommes également membres de l\'APPEL-Bénin, l\'Association Professionnelle des Éditeurs de Livres du Bénin.',
            'category' => 'Général',
            'position' => 11,
        ],
        [
            'question' => 'Comment contacter l\'équipe ProTIC Editions ?',
            'answer'   => 'Vous pouvez nous joindre par téléphone au +229 95 86 99 51 ou au 62 013 868, par email à proticeditions@gmail.com, ou nous rendre visite directement sur le Campus d\'Abomey-Calavi, K61-62 Rectorat annexe. Nos horaires sont : Lun-Ven 08h-17h et Samedi 09h-13h.',
            'category' => 'Général',
            'position' => 12,
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::FAQS as $data) {
            $faq = new Faq();
            $faq->setQuestion($data['question'])
                ->setAnswer($data['answer'])
                ->setCategory($data['category'])
                ->setPosition($data['position'])
                ->setPublished(true);

            $faq->prePersist();
            $faq->preUpdate();

            $manager->persist($faq);
        }

        $manager->flush();
    }
}
