<?php

namespace OroCRM\Bundle\MagentoBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;



use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use OroCRM\Bundle\AccountBundle\Entity\Account;
use OroCRM\Bundle\MagentoBundle\Entity\Customer;

/**
 * @Route("/customer")
 */
class CustomerController extends Controller
{
    /**
     * @Route("/", name="orocrm_magento_customer_index")
     * @AclAncestor("orocrm_magento_customer_view")
     * @Template
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orocrm_magento.customer.entity.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="orocrm_magento_customer_view", requirements={"id"="\d+"}))
     * @Acl(
     *      id="orocrm_magento_customer_view",
     *      type="entity",
     *      permission="VIEW",
     *      class="OroCRMMagentoBundle:Customer"
     * )
     * @Template
     */
    public function viewAction(Customer $customer)
    {
        return ['entity' => $customer];
    }

    /**
     * @Route("/info/{id}", name="orocrm_magento_customer_info", requirements={"id"="\d+"}))
     * @AclAncestor("orocrm_magento_customer_view")
     * @Template
     */
    public function infoAction(Customer $customer)
    {
        return ['entity' => $customer];
    }

    /**
     * @Route(
     *         "/widget/customers-info/{accountId}/{channelId}",
     *          name="orocrm_magento_widget_account_customers_info",
     *          requirements={"accountId"="\d+", "channelId"="\d+"}
     * )
     * @ParamConverter("account", class="OroCRMAccountBundle:Account", options={"id" = "accountId"})
     * @ParamConverter("channel", class="OroIntegrationBundle:Channel", options={"id" = "channelId"})
     * @AclAncestor("orocrm_magento_customer_view")
     * @Template
     */
    public function accountCustomersInfoAction(Account $account, Channel $channel)
    {
        $customers = $this->getDoctrine()
            ->getRepository('OroCRM\\Bundle\\MagentoBundle\\Entity\\Customer')
            ->findBy(array('account' => $account, 'channel' => $channel));

        return array('customers' => $customers, 'channel' => $channel);
    }

    /**
     * @Route(
     *        "/widget/customer-info/{id}/{channelId}",
     *        name="orocrm_magento_widget_customer_info",
     *        requirements={"id"="\d+", "channelId"="\d+"}
     * )
     * @ParamConverter("channel", class="OroIntegrationBundle:Channel", options={"id" = "channelId"})
     * @AclAncestor("orocrm_magento_customer_view")
     * @Template
     */
    public function customerInfoAction(Customer $customer, Channel $channel)
    {
        return array('customer' => $customer, 'channel' => $channel);
    }

    /**
     * @Route("/order/{id}", name="orocrm_magento_customer_orderplace", requirements={"id"="\d+"}))
     * @AclAncestor("orocrm_magento_customer_view")
     * @Template
     */
    public function placeOrderAction(Customer $customer)
    {
        return ['entity' => $customer];
    }

    /**
     * @Route("/addressBook/{id}", name="orocrm_magento_customer_address_book", requirements={"id"="\d+"}))
     * @AclAncestor("orocrm_magento_customer_view")
     * @Template
     */
    public function addressBookAction(Customer $customer)
    {
        return ['entity' => $customer];
    }
}
