<?php

namespace OroCRM\Bundle\DemoDataBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;



use Oro\Bundle\UserBundle\Entity\User;
use OroCRM\Bundle\CampaignBundle\Entity\Campaign;
use OroCRM\Bundle\SalesBundle\Entity\Lead;

class LoadCampaignData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var User[]
     */
    protected $users;

    /**
     * @var Lead[]
     */
    protected $leads;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroCRM\Bundle\DemoDataBundle\Migrations\Data\Demo\ORM\LoadLeadsData'
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->users = $manager->getRepository('OroUserBundle:User')->findAll();
        $this->leads = $manager->getRepository('OroCRMSalesBundle:Lead')->findAll();

        $handle = fopen(__DIR__ . DIRECTORY_SEPARATOR . 'dictionaries' . DIRECTORY_SEPARATOR. "campaigns.csv", "r");
        if ($handle) {
            $headers = array();
            if (($data = fgetcsv($handle, 1000, ",")) !== false) {
                //read headers
                $headers = $data;
            }
            $randomUser = count($this->users) - 1;

            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                $user = $this->users[mt_rand(0, $randomUser)];

                $this->setSecurityContext($user);

                $data = array_combine($headers, array_values($data));

                $campaign = $this->createCampaign($data, $user);
                $leadsNumber = mt_rand(1, 10);
                for ($i = 0; $i <= $leadsNumber; $i++) {
                    $lead = $this->getLead();
                    $lead->setCampaign($campaign);
                    $manager->persist($lead);
                }
                $manager->persist($campaign);

                $manager->flush();
            }
            fclose($handle);
        }
    }

    /**
     * @return Lead
     */
    protected function getLead()
    {
        /**
         * @var Lead
         */
        $lead = $this->leads[mt_rand(0, count($this->leads) - 1)];
        if ($lead->getCampaign()) {
            return $this->getLead();
        }

        return $lead;
    }

    protected function createCampaign(array $data, $user)
    {
        $campaign = new Campaign();
        $campaign->setName($data['Name']);
        $campaign->setCode($data['Code']);
        $campaign->setBudget($data['Budget']);
        $campaign->setOwner($user);

        return $campaign;
    }

    /**
     * @param User $user
     */
    protected function setSecurityContext($user)
    {
        $securityContext = $this->container->get('security.context');
        $token = new UsernamePasswordToken($user, $user->getUsername(), 'main');
        $securityContext->setToken($token);
    }
}
