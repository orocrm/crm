<?php

namespace OroCRM\Bundle\SalesBundle\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroCRM\Bundle\SalesBundle\Entity\LeadEmail;
use OroCRM\Bundle\SalesBundle\Entity\Lead;

class LeadEmailHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var EntityManagerInterface */
    protected $manager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        EntityManagerInterface $manager,
        SecurityFacade $securityFacade
    ) {
        $this->form    = $form;
        $this->request = $request;
        $this->manager = $manager;
        $this->securityFacade = $securityFacade;
    }

    /**
     * Process form
     *
     * @param LeadEmail $entity
     *
     * @return bool True on successful processing, false otherwise
     *
     * @throws AccessDeniedException
     */
    public function process(LeadEmail $entity)
    {
        $this->form->setData($entity);

        $submitData = [
            'email' => $this->request->request->get('email'),
            'primary' => $this->request->request->get('primary')
        ];

        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            $this->form->submit($submitData);

            if ($this->form->isValid() && $this->request->request->get('leadId')) {
                /** @var Lead $lead */
                $lead = $this->manager->find(
                    'OroCRMCSalesBundle:Lead',
                    $this->request->request->get('leadId')
                );
                if (!$this->securityFacade->isGranted('EDIT', $lead)) {
                    throw new AccessDeniedException();
                }

                if ($lead->getPrimaryEmail() && $this->request->request->get('primary') === true) {
                    return false;
                }

                $this->onSuccess($entity, $lead);

                return true;
            }
        }

        return false;
    }

    /**
     * @param $id
     * @param ApiEntityManager $manager
     * @throws \Exception
     */
    public function handleDelete($id, ApiEntityManager $manager)
    {
        /** @var LeadEmail $leadEmail */
        $leadEmail = $manager->find($id);
        if (!$this->securityFacade->isGranted('EDIT', $leadEmail->getOwner())) {
            throw new AccessDeniedException();
        }

        if ($leadEmail->isPrimary() && $leadEmail->getOwner()->getEmails()->count() === 1) {
            $em = $manager->getObjectManager();
            $em->remove($leadEmail);
            $em->flush();
        } else {
            throw new \Exception("oro.lead.email.error.delete.more_one", 500);
        }
    }

    /**
     * @param LeadEmail $entity
     * @param Lead $lead
     */
    protected function onSuccess(LeadEmail $entity, Lead $lead)
    {
        $entity->setOwner($lead);
        $this->manager->persist($entity);
        $this->manager->flush();
    }
}
