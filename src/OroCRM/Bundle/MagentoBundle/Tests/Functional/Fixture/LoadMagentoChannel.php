<?php

namespace OroCRM\Bundle\MagentoBundle\Tests\Functional\Fixture;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;



use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Model\Gender;
use OroCRM\Bundle\AccountBundle\Entity\Account;
use OroCRM\Bundle\MagentoBundle\Entity\Address as MagentoAddress;
use OroCRM\Bundle\MagentoBundle\Entity\Cart;
use OroCRM\Bundle\MagentoBundle\Entity\CartAddress;
use OroCRM\Bundle\MagentoBundle\Entity\CartItem;
use OroCRM\Bundle\MagentoBundle\Entity\CartStatus;
use OroCRM\Bundle\MagentoBundle\Entity\Customer;
use OroCRM\Bundle\MagentoBundle\Entity\CustomerGroup;
use OroCRM\Bundle\MagentoBundle\Entity\MagentoSoapTransport;
use OroCRM\Bundle\MagentoBundle\Entity\Order;
use OroCRM\Bundle\MagentoBundle\Entity\OrderItem;
use OroCRM\Bundle\MagentoBundle\Entity\Store;
use OroCRM\Bundle\MagentoBundle\Entity\Website;

class LoadMagentoChannel extends AbstractFixture
{
    /** @var ObjectManager */
    private $em;

    /** @var Channel */
    private $channel;

    /** @var MagentoSoapTransport */
    private $transport;

    /** @var array */
    private $countries;

    /** @var array */
    private $regions;

    /** @var Website */
    private $website;

    /** @var Store */
    private $store;

    /** @var CustomerGroup */
    private $customerGroup;

    /**
     * @param ObjectManager $manager
     *
     * @return Channel
     */
    public function load(ObjectManager $manager)
    {
        $this->em        = $manager;
        $this->countries = $this->loadStructure('OroAddressBundle:Country', 'getIso2Code');
        $this->regions   = $this->loadStructure('OroAddressBundle:Region', 'getCombinedCode');

        $this->createTransport()
            ->createChannel()
            ->createWebSite()
            ->createCustomerGroup()
            ->createStore();

        $address1       = $this->createAddress($this->regions['US-AZ'], $this->countries['US']);
        $address2       = $this->createAddress($this->regions['US-AZ'], $this->countries['US']);
        $magentoAddress = $this->createMagentoAddress($this->regions['US-AZ'], $this->countries['US']);
        $account        = $this->createAccount($address1, $address2);
        $customer       = $this->createCustomer(1, $account, $magentoAddress);
        $cartAddress1   = $this->createCartAddress($this->regions['US-AZ'], $this->countries['US'], 1);
        $cartAddress2   = $this->createCartAddress($this->regions['US-AZ'], $this->countries['US'], 2);
        $cartItem       = $this->createCartItem();
        $status         = $this->getStatus();
        $items          = new ArrayCollection();
        $items->add($cartItem);

        $cart = $this->createCart($cartAddress1, $cartAddress2, $customer, $items, $status);
        $this->updateCartItem($cartItem, $cart);

        $order = $this->createOrder($cart, $customer);

        $this->setReference('customer', $customer);
        $this->setReference('channel', $this->channel);
        $this->setReference('cart', $cart);
        $this->setReference('order', $order);

        $baseOrderItem = $this->createBaseOrderItem($order);
        $order->setItems([$baseOrderItem]);
        $this->em->persist($order);

        $this->em->flush();

        return $this->channel;
    }

    /**
     * @param                 $billing
     * @param                 $shipping
     * @param Customer        $customer
     * @param ArrayCollection $item
     * @param CartStatus      $status
     *
     * @return Cart
     */
    protected function createCart($billing, $shipping, Customer $customer, ArrayCollection $item, $status)
    {
        $cart = new Cart();
        $cart->setChannel($this->channel);
        $cart->setBillingAddress($billing);
        $cart->setShippingAddress($shipping);
        $cart->setCustomer($customer);
        $cart->setEmail('email@email.com');
        $cart->setCreatedAt(new \DateTime('now'));
        $cart->setUpdatedAt(new \DateTime('now'));
        $cart->setCartItems($item);
        $cart->setStatus($status);
        $cart->setItemsQty(0);
        $cart->setItemsCount(1);
        $cart->setBaseCurrencyCode('code');
        $cart->setStoreCurrencyCode('code');
        $cart->setQuoteCurrencyCode('usd');
        $cart->setStoreToBaseRate(12);
        $cart->setGrandTotal(2.54);
        $cart->setIsGuest(0);
        $cart->setStore($this->store);
        $cart->setOwner($this->getUser());

        $this->em->persist($cart);

        return $cart;
    }

    /**
     * @param $table
     * @param $method
     *
     * @return array
     */
    protected function loadStructure($table, $method)
    {
        $result   = [];
        $response = $this->em->getRepository($table)->findAll();
        foreach ($response as $row) {
            $result[call_user_func([$row, $method])] = $row;
        }

        return $result;
    }

    /**
     * @return $this
     */
    protected function createChannel()
    {
        $channel = new Channel();
        $channel->setName('Demo Web store');
        $channel->setType('magento');
        $channel->setConnectors(["customer", "order", "cart", "region"]);
        $channel->setTransport($this->transport);

        $this->em->persist($channel);
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return $this
     */
    protected function createTransport()
    {
        $transport = new MagentoSoapTransport;
        $transport->setAdminUrl('http://localhost/magento/admin');
        $transport->setApiKey('key');
        $transport->setApiUser('user');
        $transport->setIsExtensionInstalled(true);
        $transport->setIsWsiMode(false);
        $transport->setWebsiteId('1');
        $transport->setWsdlUrl('http://localhost/magento/api/v2_soap?wsdl=1');
        $transport->setWebsites([['id' => 1, 'label' => 'Website ID: 1, Stores: English, French, German']]);

        $this->em->persist($transport);
        $this->transport = $transport;

        return $this;
    }

    /**
     * @param $region
     * @param $country
     * @param $originId
     *
     * @return CartAddress
     */
    protected function createCartAddress($region, $country, $originId)
    {
        $cartAddress = new CartAddress;
        $cartAddress->setRegion($region);
        $cartAddress->setCountry($country);
        $cartAddress->setCity('City');
        $cartAddress->setStreet('street');
        $cartAddress->setPostalCode(123456);
        $cartAddress->setFirstName('John');
        $cartAddress->setLastName('Doe');
        $cartAddress->setOriginId($originId);

        $this->em->persist($cartAddress);

        return $cartAddress;
    }

    /**
     * @param $region
     * @param $country
     *
     * @return MagentoAddress
     */
    protected function createMagentoAddress($region, $country)
    {
        $address = new MagentoAddress;
        $address->setRegion($region);
        $address->setCountry($country);
        $address->setCity('City');
        $address->setStreet('street');
        $address->setPostalCode(123456);
        $address->setFirstName('John');
        $address->setLastName('Doe');
        $address->setLabel('label');
        $address->setPrimary(true);
        $address->setOrganization('oro');
        $address->setOriginId(1);

        $this->em->persist($address);

        return $address;
    }

    /**
     * @param $region
     * @param $country
     *
     * @return Address
     */
    protected function createAddress($region, $country)
    {
        $address = new Address;
        $address->setRegion($region);
        $address->setCountry($country);
        $address->setCity('City');
        $address->setStreet('street');
        $address->setPostalCode(123456);
        $address->setFirstName('John');
        $address->setLastName('Doe');

        $this->em->persist($address);

        return $address;
    }

    /**
     * @param                $oid
     * @param Account        $account
     * @param MagentoAddress $address
     *
     * @return Customer
     */
    protected function createCustomer($oid, Account $account, MagentoAddress $address)
    {
        $customer = new Customer();
        $customer->setChannel($this->channel);
        $customer->setFirstName('John');
        $customer->setLastName('Doe');
        $customer->setEmail('test@example.com');
        $customer->setOriginId($oid);
        $customer->setIsActive(true);
        $customer->setWebsite($this->website);
        $customer->setStore($this->store);
        $customer->setAccount($account);
        $customer->setGender(Gender::MALE);
        $customer->setGroup($this->customerGroup);
        $customer->setCreatedAt(new \DateTime('now'));
        $customer->setUpdatedAt(new \DateTime('now'));
        $customer->addAddress($address);
        $customer->setOwner($this->getUser());

        $this->em->persist($customer);

        return $customer;
    }

    /**
     * @return $this
     */
    protected function createWebSite()
    {
        $website = new Website();
        $website->setName('web site');
        $website->setOriginId(1);
        $website->setCode('web site code');
        $website->setChannel($this->channel);

        $this->em->persist($website);
        $this->website = $website;

        return $this;
    }

    /**
     * @return $this
     */
    protected function createStore()
    {
        $store = new Store;
        $store->setName('demo store');
        $store->setChannel($this->channel);
        $store->setCode(1);
        $store->setWebsite($this->website);
        $store->setOriginId(1);

        $this->em->persist($store);
        $this->store = $store;

        return $this;
    }

    /**
     * @param      $billing
     * @param      $shipping
     *
     * @return Account
     */
    protected function createAccount($billing, $shipping)
    {
        $account = new Account;
        $account->setName('acc');
        $account->setBillingAddress($billing);
        $account->setShippingAddress($shipping);
        $account->setOwner($this->getUser());

        $this->em->persist($account);

        return $account;
    }

    /**
     * @return $this
     */
    protected function createCustomerGroup()
    {
        $customerGroup = new CustomerGroup;
        $customerGroup->setName('group');
        $customerGroup->setChannel($this->channel);
        $customerGroup->setOriginId(1);

        $this->em->persist($customerGroup);
        $this->customerGroup = $customerGroup;

        return $this;
    }

    /**
     * @return CartItem
     */
    protected function createCartItem()
    {
        $cartItem = new CartItem();
        $cartItem->setName('item' . mt_rand(0, 99999));
        $cartItem->setDescription('something');
        $cartItem->setPrice(mt_rand(10, 99999));
        $cartItem->setProductId(1);
        $cartItem->setFreeShipping('true');
        $cartItem->setIsVirtual(1);
        $cartItem->setRowTotal(100);
        $cartItem->setTaxAmount(10);
        $cartItem->setProductType('type');
        $cartItem->setSku('sku');
        $cartItem->setQty(0);
        $cartItem->setDiscountAmount(0);
        $cartItem->setTaxPercent(0);
        $cartItem->setCreatedAt(new \DateTime('now'));
        $cartItem->setUpdatedAt(new \DateTime('now'));

        $this->em->persist($cartItem);

        return $cartItem;
    }

    /**
     * @return CartStatus
     */
    protected function getStatus()
    {
        $status = $this->em->getRepository('OroCRMMagentoBundle:CartStatus')->findOneBy(['name' => 'open']);

        return $status;
    }

    /**
     * @param CartItem $cartItem
     * @param Cart     $cart
     *
     * @return $this
     */
    protected function updateCartItem(CartItem $cartItem, Cart $cart)
    {
        $cartItem->setCart($cart);
        $this->em->persist($cartItem);

        return $this;
    }

    /**
     * @param Cart     $cart
     * @param Customer $customer
     *
     * @return Order
     */
    protected function createOrder(Cart $cart, Customer $customer)
    {
        $order = new Order();
        $order->setChannel($this->channel);
        $order->setStatus('open');
        $order->setIncrementId('one');
        $order->setCreatedAt(new \DateTime('now'));
        $order->setUpdatedAt(new \DateTime('now'));
        $order->setCart($cart);
        $order->setStore($this->store);
        $order->setCustomer($customer);
        $order->setCustomerEmail('customer@email.com');
        $order->setDiscountAmount(34.40);
        $order->setTaxAmount(12.47);
        $order->setShippingAmount(5);
        $order->setTotalPaidAmount(17.85);
        $order->setTotalInvoicedAmount(11);
        $order->setTotalRefundedAmount(4);
        $order->setTotalCanceledAmount(0);
        $order->setShippingMethod('some unique shipping method');
        $order->setRemoteIp('unique ip');
        $order->setGiftMessage('some very unique gift message');
        $order->setOwner($this->getUser());

        $this->em->persist($order);

        return $order;
    }

    protected function createBaseOrderItem(Order $order)
    {
        $orderItem = new OrderItem();
        $orderItem->setId(mt_rand(0, 9999));
        $orderItem->setName('some order item');
        $orderItem->setSku('some sku');
        $orderItem->setQty(1);
        $orderItem->setOrder($order);
        $orderItem->setCost(51.00);
        $orderItem->setPrice(75.00);
        $orderItem->setWeight(6.12);
        $orderItem->setTaxPercent(2);
        $orderItem->setTaxAmount(1.5);
        $orderItem->setDiscountPercent(4);
        $orderItem->setDiscountAmount(0);
        $orderItem->setRowTotal(234);

        $this->em->persist($orderItem);

        return $orderItem;
    }

    /**
     * @return User
     */
    protected function getUser()
    {
        $user = $this->em->getRepository('OroUserBundle:User')->findOneBy(['username' => 'admin']);

        return $user;
    }
}
