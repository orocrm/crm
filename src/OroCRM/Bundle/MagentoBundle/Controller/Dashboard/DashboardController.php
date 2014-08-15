<?php

namespace OroCRM\Bundle\MagentoBundle\Controller\Dashboard;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;



use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use OroCRM\Bundle\MagentoBundle\Entity\Repository\CartRepository;

class DashboardController extends Controller
{
    /**
     * @Route(
     *      "/sales_flow_b2c/chart/{widget}",
     *      name="orocrm_magento_dashboard_sales_flow_b2c_chart",
     *      requirements={"widget"="[\w_-]+"}
     * )
     * @Template("OroCRMSalesBundle:Dashboard:salesFlowChart.html.twig")
     */
    public function mySalesFlowB2CAction($widget)
    {
        $dateTo = new \DateTime('now', new \DateTimeZone('UTC'));
        $dateFrom = new \DateTime(
            $dateTo->format('Y') . '-01-' . ((ceil($dateTo->format('n') / 3) - 1) * 3 + 1),
            new \DateTimeZone('UTC')
        );

        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');
        $workflow = $workflowManager->getApplicableWorkflowByEntityClass(
            'OroCRM\Bundle\MagentoBundle\Entity\Cart'
        );

        /** @var CartRepository $shoppingCartRepository */
        $shoppingCartRepository = $this->getDoctrine()->getRepository('OroCRMMagentoBundle:Cart');

        $data = $shoppingCartRepository->getFunnelChartData(
            $dateFrom,
            $dateTo,
            $workflow,
            $this->get('oro_security.acl_helper')
        );

        $widgetAttr = $this->get('oro_dashboard.widget_attributes')->getWidgetAttributesForTwig($widget);
        $widgetAttr['chartView'] = $this->get('oro_chart.view_builder')
            ->setArrayData($data)
            ->setOptions(
                array(
                    'name' => 'flow_chart',
                    'settings' => array('quarterDate' => $dateFrom),
                    'data_schema' => array(
                        'label' => array('field_name' => 'label'),
                        'value' => array('field_name' => 'value'),
                        'isNozzle' => array('field_name' => 'isNozzle'),
                    )
                )
            )
            ->getView();

        return $widgetAttr;
    }
}
