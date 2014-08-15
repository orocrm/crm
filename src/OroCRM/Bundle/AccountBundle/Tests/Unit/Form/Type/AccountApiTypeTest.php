<?php

namespace OroCRM\Bundle\AccountBundle\Tests\Unit\Form\Type;

use BeSimple\SoapCommon\Type\KeyValue\Boolean;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;



use OroCRM\Bundle\AccountBundle\Form\Type\AccountApiType;

class AccountApiTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AccountApiType
     */
    private $type;

    /**
     * init environment
     */
    public function init($havePrivilege = true)
    {
        $nameFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NameFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $router = $this->getMockBuilder('Symfony\Component\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $securityFacade->expects($this->any())
            ->method('isGranted')
            ->with('orocrm_contact_view')
            ->will($this->returnValue($havePrivilege));

        $this->type = new AccountApiType($router, $nameFormatter, $securityFacade);
    }

    public function testSetDefaultOptions()
    {
        $this->init();
        /** @var OptionsResolverInterface $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');

        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));

        $this->type->setDefaultOptions($resolver);
    }

    public function testName()
    {
        $this->init();
        $this->assertEquals('account', $this->type->getName());
    }

    public function testAddEntityFields()
    {
        $this->init();
        /** @var FormBuilderInterface $builder */
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->at(0))
            ->method('add')
            ->with('name', 'text')
            ->will($this->returnSelf());

        $builder->expects($this->at(1))
            ->method('add')
            ->with('tags', 'oro_tag_select')
            ->will($this->returnSelf());
        $builder->expects($this->at(2))
            ->method('add')
            ->with('default_contact', 'oro_entity_identifier')
            ->will($this->returnSelf());
        $builder->expects($this->at(3))
            ->method('add')
            ->with('contacts', 'oro_multiple_entity')
            ->will($this->returnSelf());
        $builder->expects($this->at(4))
            ->method('add')
            ->with('shippingAddress', 'oro_address')
            ->will($this->returnSelf());
        $builder->expects($this->at(5))
            ->method('add')
            ->with('billingAddress', 'oro_address')
            ->will($this->returnSelf());

        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->isInstanceOf('Symfony\Component\EventDispatcher\EventSubscriberInterface'));

        $this->type->buildForm($builder, []);
    }

    public function testAddEntityFieldsWithoutContactPermission()
    {
        $this->init(false);
        /** @var FormBuilderInterface $builder */
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->at(0))
            ->method('add')
            ->with('name', 'text')
            ->will($this->returnSelf());

        $builder->expects($this->at(1))
            ->method('add')
            ->with('tags', 'oro_tag_select')
            ->will($this->returnSelf());
        $builder->expects($this->at(2))
            ->method('add')
            ->with('shippingAddress', 'oro_address')
            ->will($this->returnSelf());
        $builder->expects($this->at(3))
            ->method('add')
            ->with('billingAddress', 'oro_address')
            ->will($this->returnSelf());

        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->isInstanceOf('Symfony\Component\EventDispatcher\EventSubscriberInterface'));

        $this->type->buildForm($builder, []);
    }
}
