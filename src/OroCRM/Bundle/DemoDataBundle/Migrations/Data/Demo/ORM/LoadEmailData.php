<?php

namespace OroCRM\Bundle\DemoDataBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;



use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use OroCRM\Bundle\ContactBundle\Entity\Contact;

class LoadEmailData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    /**
     * @var string
     */
    protected $templates;

    /**
     * @var EmailEntityBuilder
     */
    protected $emailEntityBuilder;

    /**
     * @var Processor
     */
    protected $mailerProcessor;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['OroCRM\Bundle\DemoDataBundle\Migrations\Data\Demo\ORM\LoadContactData',];
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        if (!$container) {
            return;
        }

        $this->emailEntityBuilder = $container->get('oro_email.email.entity.builder');
        $this->mailerProcessor = $container->get('oro_email.mailer.processor');
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $om)
    {
        $this->loadEmailTemplates();
        $this->loadEmailsDemo($om);
        $om->flush();
    }

    protected function loadEmailTemplates()
    {
        $handle = fopen(__DIR__ . DIRECTORY_SEPARATOR . 'dictionaries' . DIRECTORY_SEPARATOR. "emails.csv", "r");
        if ($handle) {
            $headers = array();
            if (($data = fgetcsv($handle, 1000, ",")) !== false) {
                //read headers
                $headers = $data;
            }
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                $this->templates[] = array_combine($headers, array_values($data));
            }
        }
    }

    /**
     * @param ObjectManager $om
     */
    protected function loadEmailsDemo(ObjectManager $om)
    {
        $contacts = $om->getRepository('OroCRMContactBundle:Contact')->findAll();
        $contactCount = count($contacts);

        for ($i = 0; $i < 100; ++$i) {
            $contactRandom = rand(0, $contactCount - 1);

            /** @var Contact $contact */
            $contact = $contacts[$contactRandom];
            $owner = $contact->getOwner();
            $origin = $this->mailerProcessor->getEmailOrigin($owner->getEmail());

            $randomTemplate = array_rand($this->templates);

            $email = $this->emailEntityBuilder->email(
                $this->templates[$randomTemplate]['Subject'],
                $owner->getEmail(),
                $contact->getPrimaryEmail()->getEmail(),
                new \DateTime('now'),
                new \DateTime('now'),
                new \DateTime('now')
            );

            $email->addFolder($origin->getFolder(EmailFolder::SENT));

            $emailBody = $this->emailEntityBuilder->body(
                "Hi,\n" . $this->templates[$randomTemplate]['Text'],
                false,
                true
            );
            $email->setEmailBody($emailBody);
            $email->setMessageId(sprintf('id.%s@%s', uniqid(), '@bap.migration.generated'));

            $this->emailEntityBuilder->getBatch()->persist($om);
        }
    }
}
