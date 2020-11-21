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
            'items' => $this->formatOrdersArray($this->getOrdersForCart($args['cartId']))
        ];
    }

    /**
     * Format order models for graphql schema.
     * Copied from the Magento resolver.
     *
     * @param OrderInterface[] $orderModels
     * @return array
     */
    private function formatOrdersArray(array $orderModels)
    {
        $ordersArray = [];
        foreach ($orderModels as $orderModel) {
            $ordersArray[] = [
                'created_at' => $orderModel->getCreatedAt(),
                'grand_total' => $orderModel->getGrandTotal(),
                'id' => base64_encode($orderModel->getEntityId()),
                'increment_id' => $orderModel->getIncrementId(),
                'number' => $orderModel->getIncrementId(),
                'order_date' => $orderModel->getCreatedAt(),
                'order_number' => $orderModel->getIncrementId(),
                'status' => $orderModel->getStatusLabel(),
                'shipping_method' => $orderModel->getShippingDescription(),
                'shipping_address' => $this->orderAddress->getOrderShippingAddress($orderModel),
                'billing_address' => $this->orderAddress->getOrderBillingAddress($orderModel),
                'payment_methods' => $this->orderPayments->getOrderPaymentMethod($orderModel),
                'model' => $orderModel,
                'email' => $orderModel->getCustomerEmail()
            ];
        }
        return $ordersArray;
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

        $cartCustomerId = (int)$cart->getCustomerId();

        /* Not a guest cart, throw */
        if (0 !== $cartCustomerId) {
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
    private function getOrdersForCart(string $cartHash)
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

        $orders = $this->collectionFactory->create(null)->getItems();

        /** @param OrderInterface $order */
        $isCartOrder = function ($order) use ($orderId) {
            return $order->getIncrementId() === $orderId;
        };

        return array_values(array_filter($orders, $isCartOrder));
    }
}
