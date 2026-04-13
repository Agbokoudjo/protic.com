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

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\ListMapper;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 *
 * Classe de base pour tous les administrateurs d'entités Doctrine.
 */
abstract class WlindablaAdmin extends AbstractAdmin
{
    protected string $labelShow = 'show';

    protected string $labelEdit = 'Edit';

    protected string $labelList = 'List';

    protected string $labelCreate = "Create";

    public function __construct(
        private readonly ?string $label_list_id = null,
        private readonly ?string $label_show_id = null,
        private readonly ?string $label_create_id = null,
        private readonly ?string $label_edit_id=null
    ) {
        
    }


    protected function preValidate(object $object): void
    {
        if (!$this->hasRequest()) {
            return;
        }

        $request = $this->getRequest();

        if ($request->isXmlHttpRequest() && $this->isDynamicFormRequest($request)) {
            return;
        }

        $this->setTranslationDomain('validators');
    }

    public function isDynamicFormRequest(): bool
    {
        return (bool) $this->getRequest()->query->getBoolean('dynamic_form',false) === true;
    }

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::SORT_BY] = 'id';
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
    }

    protected function configureDefaultFilterValues(array &$filterValues): void
    {
        $filterValues['_page'] = 1; // La page par défaut
        $filterValues['_per_page'] = 10; // Le nombre d'éléments par page
    }

    
    protected function configureListFields(ListMapper $list): void
    {
        // $list->add(ListMapper::NAME_BATCH, ListMapper::TYPE_BATCH, [
        //     'label' => 'batch',
        //     'sortable' => false,
        //     'virtual_field' => true,
        //     'template' => $this->getTemplateRegistry()->getTemplate('batch'),
        // ]);
    }

    /**
     * Get the value of labelShow
     */
    public function getLabelShow(object $object): string
    {
        if ($this->label_show_id) {
            $this->labelShow = $this->getTranslator()
                ->trans($this->label_show_id, ['%name%' => $this->toString($object)], 'label_show', $this->getRequest()->getLocale());
        }

        return $this->labelShow;
    }

    /**
     * Set the value of labelShow
     */
    public function setLabelShow(string $labelShow): void
    {
        $this->labelShow = $labelShow;
    }

    /**
     * Get the value of labelEdit
     */
    public function getLabelEdit(object $object): string
    {
        if ($this->label_edit_id) {
            $this->labelEdit = $this->getTranslator()
                ->trans($this->label_edit_id, ['%name%' => $this->toString($object)], 'label_edit', $this->getRequest()->getLocale());
        }
        return $this->labelEdit;
    }

    /**
     * Set the value of labelEdit
     */
    public function setLabelEdit(string $labelEdit): self
    {
        $this->labelEdit = $labelEdit;

        return $this;
    }

    /**
     * Get the value of labelList
     */
    final public function getLabelList(): string
    {
        if ($this->label_list_id) {
            $this->labelList = $this->getTranslator()
                ->trans($this->label_list_id, [], 'label_list', $this->getRequest()->getLocale());
        }

        return $this->labelList;
    }


    /**
     * Set the value of labelList
     */
    final public function setLabelList(string $labelList): void
    {
        $this->labelList = $labelList;
    }

    /**
     * Get the value of labelCreate
     */
    public function getLabelCreate(): string
    {
        if ($this->label_create_id) {
            $this->labelCreate = $this->getTranslator()
                ->trans($this->label_create_id, [], 'label_create', $this->getRequest()->getLocale());
        }

        return $this->labelCreate;
    }

    /**
     * Set the value of labelCreate
     */
    public function setLabelCreate(string $labelCreate): void
    {
        $this->labelCreate = $labelCreate;
    }

    protected function trans(string $messageId, array $parameters = [], string $domain = "messages", ?string $locale = "fr"): string
    {
        return $this->getTranslator()->trans($messageId, $parameters, $domain, $locale ?? $this->locale());
    }

    protected function locale(): string
    {

        if (!$this->hasRequest()) {
            return 'fr';
        }

        return $this->getRequest()->getLocale();
    }

    /**
     * Personnalisation du label de la vue "Show" (Détails du domaine...)
     */
    public function toString(object $object): string
    {
        if (method_exists($object, '__toString') && null !== $object->__toString()) {
            return $object->__toString();
        }

        return $object->getId();
    }
}
