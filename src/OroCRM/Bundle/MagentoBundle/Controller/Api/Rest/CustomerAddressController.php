<?php

namespace OroCRM\Bundle\MagentoBundle\Controller\Api\Rest;

use FOS\Rest\Util\Codes;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\JsonResponse;



use Oro\Bundle\AddressBundle\Entity\AbstractTypedAddress;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use OroCRM\Bundle\MagentoBundle\Entity\Customer;

/**
 * @NamePrefix("oro_api_")
 */
class CustomerAddressController extends RestController implements ClassResourceInterface
{
    /**
     * REST GET list
     *
     * @ApiDoc(
     *      description="Get all addresses items",
     *      resource=true
     * )
     * @AclAncestor("orocrm_magento_customer_view")
     * @param int $customerId
     *
     * @return JsonResponse
     */
    public function cgetAction($customerId)
    {
        /** @var Customer $customer */
        $customer = $this->getManager()->find($customerId);
        $result   = [];

        if (!empty($customer)) {
            $items = $customer->getAddresses();

            foreach ($items as $item) {
                $result[] = $this->getPreparedItem($item);
            }
        }

        return new JsonResponse(
            $result,
            empty($customer) ? Codes::HTTP_NOT_FOUND : Codes::HTTP_OK
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('orocrm_magento.customer.manager.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        throw new \BadMethodCallException('Form is not available.');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        throw new \BadMethodCallException('FormHandler is not available.');
    }

    /**
     * {@inheritDoc}
     */
    protected function getPreparedItem($entity, $resultFields = [])
    {
        // convert addresses to plain array
        $addressTypesData = [];

        /** @var $entity AbstractTypedAddress */
        foreach ($entity->getTypes() as $addressType) {
            $addressTypesData[] = parent::getPreparedItem($addressType);
        }

        $result                = parent::getPreparedItem($entity);
        $result['types']       = $addressTypesData;
        $result['countryIso2'] = $entity->getCountry()->getIso2Code();
        $result['countryIso3'] = $entity->getCountry()->getIso3Code();
        $result['regionCode']  = $entity->getRegionCode();

        unset($result['owner']);

        return $result;
    }
}
