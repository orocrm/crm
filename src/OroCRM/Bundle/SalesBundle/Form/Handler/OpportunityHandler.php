<?php

namespace OroCRM\Bundle\SalesBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;



use OroCRM\Bundle\SalesBundle\Entity\Opportunity;

class OpportunityHandler
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @param FormInterface $form
     * @param Request       $request
     * @param ObjectManager $manager
     */
    public function __construct(FormInterface $form, Request $request, ObjectManager $manager)
    {
        $this->form    = $form;
        $this->request = $request;
        $this->manager = $manager;
    }

    /**
     * @param  Opportunity $entity
     * @return bool
     */
    public function process(Opportunity $entity)
    {
        $this->form->setData($entity);

        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $this->onSuccess($entity);

                return true;
            }
        }

        return false;
    }

    /**
     * @param Opportunity $entity
     */
    protected function onSuccess(Opportunity $entity)
    {
        $this->manager->persist($entity);
        $this->manager->flush();
    }
}
