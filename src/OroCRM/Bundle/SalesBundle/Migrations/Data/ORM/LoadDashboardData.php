<?php

namespace OroCRM\Bundle\SalesBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;



use Oro\Bundle\DashboardBundle\Migrations\Data\ORM\AbstractDashboardFixture;

class LoadDashboardData extends AbstractDashboardFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\DashboardBundle\Migrations\Data\ORM\LoadDashboardData'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $mainDashboard = $this->findAdminDashboardModel($manager, 'main');

        if ($mainDashboard) {
            $mainDashboard
                ->addWidget($this->createWidgetModel('opportunities_by_lead_source_chart', [1, 80]))
                ->addWidget($this->createWidgetModel('opportunities_by_state', [0, 90]))
                ->addWidget($this->createWidgetModel('my_sales_flow_b2b_chart', [1, 120]));

            $manager->flush();
        }
    }
}
