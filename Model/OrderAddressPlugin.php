<?php
declare(strict_types=1);

namespace Graycore\GuestOrdersGraphQl\Model;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\SalesGraphQl\Model\Order\OrderAddress;

/**
 * Adds region code to address data
 */
class OrderAddressPlugin
{
    /**
     * Get the order Shipping address
     *
     * @param OrderAddress $subject
     * @param array $result
     * @param OrderInterface $order
     * @return array|null
     */
    public function afterGetOrderShippingAddress(
        OrderAddress $subject,
        array $result,
        OrderInterface $order
    ): ?array {
        $shippingAddress = $order->getShippingAddress();
        if (!$shippingAddress) {
            return $result;
        }

        return array_merge($result, [
            'region_code' => $shippingAddress->getRegionCode()
        ]);
    }

    /**
     * Get the order billing address
     *
     * @param OrderAddress $subject
     * @param array $result
     * @param OrderInterface $order
     * @return array|null
     */
    public function afterGetOrderBillingAddress(
        OrderAddress $subject,
        array $result,
        OrderInterface $order
    ): ?array {
        $billingAddress = $order->getBillingAddress();
        if (!$billingAddress) {
            return $result;
        }

        return array_merge($result, [
            'region_code' => $billingAddress->getRegionCode()
        ]);
    }
}
