<?php

namespace OroCRM\Bundle\MagentoBundle\ImportExport\Serializer;

use OroCRM\Bundle\MagentoBundle\Entity\Address as MagentoAddress;
use OroCRM\Bundle\MagentoBundle\ImportExport\Serializer\Normalizer\CompositeNormalizer;
use OroCRM\Bundle\MagentoBundle\Provider\MagentoConnectorInterface;

class MagentoAddressNormalizer extends CompositeNormalizer
{
    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = array())
    {
        return MagentoConnectorInterface::CUSTOMER_ADDRESS_TYPE == $type;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = array())
    {
        return $data instanceof MagentoAddress;
    }
}
