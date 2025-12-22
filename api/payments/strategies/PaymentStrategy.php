<?php
/**
 * Payment Strategy Interface
 * Defines the contract for all payment methods
 */

interface PaymentStrategy
{
    /**
     * Process payment and create order
     * 
     * @param array $orderData Order information (cart, shipping_info, amounts, etc.)
     * @return array Payment result ['success' => bool, 'data' => array, 'message' => string]
     */
    public function processPayment(array $orderData): array;

    /**
     * Validate payment-specific requirements
     * 
     * @param array $orderData Order data to validate
     * @return array Validation result ['valid' => bool, 'errors' => array]
     */
    public function validate(array $orderData): array;

    /**
     * Get payment method name
     * 
     * @return string Payment method identifier (COD, STRIPE, etc.)
     */
    public function getPaymentMethod(): string;
}
