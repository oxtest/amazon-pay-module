<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\AmazonPay\Core;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\AmazonPay\Core\Helper\PhpHelper;

class Payload
{
    /**
     * @var string
     */
    private $paymentIntent;

    /**
     * @var bool
     */
    private $canHandlePendingAuthorization;

    /**
     * @var string
     */
    private $merchantStoreName;

    /**
     * @var string
     */
    private $noteToBuyer;

    /**
     * @var string
     */
    private $paymentDetailsChargeAmount;

    /**
     * @var string
     */
    private $checkoutChargeAmount;

    /**
     * @var string
     */
    private $captureAmount;

    /**
     * @var string
     */
    private $currencyCode;

    /**
     * @var string
     */
    private $softDescriptor;

    /**
     * @var string
     */
    private $merchantReferenceId;

    /**
     * @return array
     */
    public function getData(): array
    {
        $data = [];

        if (
            is_bool($this->canHandlePendingAuthorization) ||
            !empty($this->paymentIntent) ||
            !empty($this->paymentDetailsChargeAmount)
        ) {
            $data['paymentDetails'] = [];
            if (is_bool($this->canHandlePendingAuthorization)) {
                $data['paymentDetails']['canHandlePendingAuthorization'] = $this->canHandlePendingAuthorization;
            }

            if (!empty($this->paymentIntent)) {
                $data['paymentDetails']['paymentIntent'] = $this->paymentIntent;
            }

            if (!empty($this->paymentDetailsChargeAmount)) {
                $data['paymentDetails']['chargeAmount'] = [];
                $data['paymentDetails']['chargeAmount']['amount'] = $this->paymentDetailsChargeAmount;
                $data['paymentDetails']['chargeAmount']['currencyCode'] = $this->currencyCode;
            }
        }

        if (!empty($this->captureAmount)) {
            $data['captureAmount'] = [];
            $data['captureAmount']['amount'] = $this->captureAmount;
            $data['captureAmount']['currencyCode'] = $this->currencyCode;
        }

        if (!empty($this->softDescriptor)) {
            $data['softDescriptor'] = $this->softDescriptor;
        } else {
            $data = $this->addMerchantMetaData($data);
        }

        if (!empty($this->checkoutChargeAmount)) {
            $data['chargeAmount'] = [];
            $data['chargeAmount']['amount'] = $this->checkoutChargeAmount;
            $data['chargeAmount']['currencyCode'] = $this->currencyCode;
        }

        return $data;
    }

    /**
     * @param string $paymentIntent
     */
    public function setPaymentIntent($paymentIntent): void
    {
        $this->paymentIntent = $paymentIntent;
    }

    /**
     * @param string $merchantStoreName
     */
    public function setMerchantStoreName($merchantStoreName): void
    {
        $this->merchantStoreName = $merchantStoreName;
    }

    /**
     * @param string $noteToBuyer
     */
    public function setNoteToBuyer($noteToBuyer): void
    {
        $this->noteToBuyer = $noteToBuyer;
    }

    /**
     * @param string $currencyCode
     */
    public function setCurrencyCode($currencyCode): void
    {
        $this->currencyCode = $currencyCode;
    }

    /**
     * @param bool $canHandlePendingAuthorization
     */
    public function setCanHandlePendingAuthorization($canHandlePendingAuthorization): void
    {
        $this->canHandlePendingAuthorization = $canHandlePendingAuthorization;
    }

    /**
     * @param string $paymentDetailsChargeAmount
     */
    public function setPaymentDetailsChargeAmount($paymentDetailsChargeAmount): void
    {
        $this->paymentDetailsChargeAmount = PhpHelper::getMoneyValue((float)$paymentDetailsChargeAmount);
    }

    /**
     * @param string $merchantReferenceId
     */
    public function setMerchantReferenceId($merchantReferenceId): void
    {
        $this->merchantReferenceId = $merchantReferenceId;
    }

    /**
     * @param string $softDescriptor
     */
    public function setSoftDescriptor($softDescriptor): void
    {
        $this->softDescriptor = $softDescriptor;
    }

    /**
     * @param string $captureAmount
     */
    public function setCaptureAmount($captureAmount): void
    {
        $this->captureAmount = PhpHelper::getMoneyValue((float)$captureAmount);
    }

    /**
     * @param string $checkoutChargeAmount
     */
    public function setCheckoutChargeAmount($checkoutChargeAmount): void
    {
        $this->checkoutChargeAmount = $checkoutChargeAmount;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function addMerchantMetaData(array $data): array
    {
        $data['merchantMetadata'] = [];
        $data['merchantMetadata']['merchantReferenceId'] = $this->merchantReferenceId;
        $data['merchantMetadata']['merchantStoreName'] = $this->merchantStoreName;
        $data['merchantMetadata']['noteToBuyer'] = $this->noteToBuyer;
        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    public function removeMerchantMetadata(array $data): array
    {
        unset($data['merchantMetadata']);
        return $data;
    }
}
