<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Graycore\GuestOrdersGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use Magento\SalesGraphQl\Model\Order\OrderAddress;
use Magento\SalesGraphQl\Model\Order\OrderPayments;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;

/**
 * Orders data resolver
 */
class GuestOrders implements ResolverInterface
{
    /**
     * @var OrderAddress
     */
    private $orderAddress;

    /**
     * @var OrderPayments
     */
    private $orderPayments;

    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var CollectionFactoryInterface
     */
    private $collectionFactory;

    /**
     * @param OrderAddress $orderAddress
     * @param OrderPayments $orderPayments
     * @param CollectionFactoryInterface $collectionFactory
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        OrderAddress $orderAddress,
        OrderPayments $orderPayments,
        CollectionFactoryInterface $collectionFactory,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        CartRepositoryInterface $cartRepository
    ) {
        $this->orderAddress = $orderAddress;
        $this->orderPayments = $orderPayments;
        $this->collectionFactory = $collectionFactory;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->cartRepository = $cartRepository;
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        return [
            'items' => $this->formatOrder($this->getOrderForCart($args['cartId']))
        ];
    }

    /**
     * Format order models for graphql schema.
     * Copied from the Magento resolver.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    private function formatOrder(\Magento\Sales\Model\Order $order)
    {
        return [
            [
                'created_at' => $order->getCreatedAt(),
                'grand_total' => $order->getGrandTotal(),
                'id' => base64_encode($order->getEntityId() ?? ''),
                'increment_id' => $order->getIncrementId(),
                'number' => $order->getIncrementId(),
                'order_date' => $order->getCreatedAt(),
                'order_number' => $order->getIncrementId(),
                'status' => $order->getStatusLabel(),
                'shipping_method' => $order->getShippingDescription(),
                'shipping_address' => $this->orderAddress->getOrderShippingAddress($order),
                'billing_address' => $this->orderAddress->getOrderBillingAddress($order),
                'payment_methods' => $this->orderPayments->getOrderPaymentMethod($order),
                'model' => $order,
                'email' => $order->getCustomerEmail()
            ]
        ];
    }

    /**
     * Finds the cart object corresponding to the passed cart hash.
     *
     * @param string $cartHash the hashed cart ID
     * @throws GraphQlAuthorizationException
     * @throws GraphQlNoSuchEntityException
     */
    private function getCart(string $cartHash)
    {
        $cart = null;

        try {
            $cartId = $this->maskedQuoteIdToQuoteId->execute($cartHash);
            $cart = $this->cartRepository->get($cartId);
        } catch (NoSuchEntityException $exception) {
            throw new GraphQlNoSuchEntityException(
                __('Could not find a cart with ID "%masked_cart_id"', ['masked_cart_id' => $cartHash])
            );
        }

        /* Not a guest cart, throw */
        if (!$cart->getCustomerIsGuest()) {
            throw new GraphQlAuthorizationException(
                __(
                    'The cart "%masked_cart_id" is not a guest cart',
                    ['masked_cart_id' => $cartHash]
                )
            );
        }

        return $cart;
    }

    /**
     * Finds the order object corresponding to the passed cart hash.
     *
     * @param string $cartHash the hashed cart ID
     * @throws GraphQlNoSuchEntityException
     */
    private function getOrderForCart(string $cartHash)
    {
        $orderId = $this->getCart($cartHash)->getReservedOrderId();

        if (!$orderId) {
            throw new GraphQlNoSuchEntityException(
                __(
                    'Could not find an order associated with cart with ID "%masked_cart_id"',
                    ['masked_cart_id' => $cartHash]
                )
            );
        }



        $order = $this->collectionFactory->create()->addFieldToSearchFilter('increment_id', $orderId)->getFirstItem();

        if (!$order->getIncrementId()) {
            throw new GraphQlNoSuchEntityException(
                __(
                    'Could not find an order associated with cart with ID "%masked_cart_id"',
                    ['masked_cart_id' => $cartHash]
                )
            );
        }
        return $order;
    }
}
