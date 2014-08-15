<?php

namespace OroCRM\Bundle\AccountBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;



use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Form\Handler\TagHandlerInterface;
use OroCRM\Bundle\AccountBundle\Entity\Account;
use OroCRM\Bundle\ContactBundle\Entity\Contact;

class AccountHandler implements TagHandlerInterface
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
     * @var TagManager
     */
    protected $tagManager;

    /**
     *
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
     * Process form
     *
     * @param  Account $entity
     * @return bool True on successful processing, false otherwise
     */
    public function process(Account $entity)
    {
        $this->form->setData($entity);

        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $this->form->submit($this->request);
            $this->handleContacts($entity);

            if ($this->form->isValid()) {
                $this->onSuccess($entity);

                return true;
            }
        }

        return false;
    }

    /**
     * @param Account $entity
     */
    protected function handleContacts($entity)
    {
        if ($this->form->has('contacts')) {
            $contacts = $this->form->get('contacts');
            $this->appendContacts($entity, $contacts->get('added')->getData());
            $this->removeContacts($entity, $contacts->get('removed')->getData());
        }
    }

    /**
     * "Success" form handler
     *
     * @param Account $entity
     */
    protected function onSuccess(Account $entity)
    {
        $this->manager->persist($entity);
        $this->manager->flush();
        $this->tagManager->saveTagging($entity);
    }

    /**
     * Append contacts to account
     *
     * @param Account $account
     * @param Contact[] $contacts
     */
    protected function appendContacts(Account $account, array $contacts)
    {
        foreach ($contacts as $contact) {
            $account->addContact($contact);
        }
    }

    /**
     * Remove contacts from account
     *
     * @param Account $account
     * @param Contact[] $contacts
     */
    protected function removeContacts(Account $account, array $contacts)
    {
        foreach ($contacts as $contact) {
            $account->removeContact($contact);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setTagManager(TagManager $tagManager)
    {
        $this->tagManager = $tagManager;
    }
}
