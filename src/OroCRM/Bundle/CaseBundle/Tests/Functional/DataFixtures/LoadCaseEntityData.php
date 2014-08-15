<?php

namespace OroCRM\Bundle\CaseBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadCaseEntityData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    protected $casesData = array(
        array(
            'subject'       => 'Case #1',
            'description'   => 'Case #1: Description',
            'comments'      => array(
                array(
                    'message'      => 'Case #1 Comment #1',
                    'public'    => true,
                    'createdAt' => '2014-01-01 13:00:00',
                    'contact'   => 'orocrm_case_contact'
                ),
                array(
                    'message'      => 'Case #1 Comment #2',
                    'public'        => true,
                    'createdAt' => '2014-01-01 14:00:00',
                ),
                array(
                    'message'   => 'Case #1 Comment #3',
                    'public'    => false,
                    'createdAt' => '2014-01-01 15:00:00',
                )
            ),
            'reportedAt'     => '2014-01-01 13:00:00',
            'relatedContact' => 'orocrm_case_contact'
        ),
        array(
            'subject'       => 'Case #2',
            'description'   => 'Case #2: Description',
            'comments'      => array(),
            'reportedAt'    => '2014-01-01 14:00:00'
        ),
        array(
            'subject'       => 'Case #3',
            'description'   => 'Case #3: Description',
            'comments'      => array(),
            'reportedAt'    => '2014-01-01 15:00:00'
        ),
    );

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $caseManager = $this->container->get('orocrm_case.manager');

        $adminUser = $manager->getRepository('OroUserBundle:User')->findOneByUsername('admin');

        foreach ($this->casesData as $caseData) {
            $case = $caseManager->createCase()
                ->setSubject($caseData['subject'])
                ->setDescription($caseData['description'])
                ->setReportedAt(new \DateTime($caseData['reportedAt'], new \DateTimeZone('UTC')))
                ->setOwner($adminUser);

            if (isset($caseData['relatedContact'])) {
                $case->setRelatedContact($this->getReference($caseData['relatedContact']));
            }

            foreach ($caseData['comments'] as $commentData) {
                $comment = $caseManager->createComment($case)
                    ->setMessage($commentData['message'])
                    ->setPublic($commentData['public'])
                    ->setCreatedAt(new \DateTime($commentData['createdAt'], new \DateTimeZone('UTC')))
                    ->setOwner($adminUser);

                if (isset($commentData['contact'])) {
                    $comment->setContact($this->getReference($commentData['contact']));
                }
            }

            $manager->persist($case);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return array('OroCRM\\Bundle\\CaseBundle\\Tests\\Functional\\DataFixtures\\LoadContactData');
    }
}
