<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\AmazonPay\Model;

use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidSolutionCatalysts\AmazonPay\Core\AmazonService;
use OxidSolutionCatalysts\AmazonPay\Core\Constants;
use OxidSolutionCatalysts\AmazonPay\Core\Helper\PhpHelper;
use OxidSolutionCatalysts\AmazonPay\Core\Provider\OxidServiceProvider;

/**
 * @mixin \OxidEsales\Eshop\Application\Model\Order
 */
class Order extends Order_parent
{
    /** @var AmazonService */
    private $amazonService;

    /**
     * Security and Cleanup before finalize order
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket              Basket object
     * @param object                                     $oUser                Current User object
     *
     * @return int|null
     *
     * @psalm-return 2|null
     */
    protected function prepareFinalizeOrder(Basket $oBasket, $oUser)
    {
        // if payment is 'oxidamazon' but we do not have a Amazon Pay Session
        // stop finalize order
        if (
            $oBasket->getPaymentId() === Constants::PAYMENT_ID &&
            !OxidServiceProvider::getAmazonService()->isAmazonSessionActive()
        ) {
            return self::ORDER_STATE_PAYMENTERROR; // means no authentication
        }
    }

    /**
     * Order checking, processing and saving method.
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket              Basket object
     * @param object                                     $oUser                Current User object
     * @param bool                                       $blRecalculatingOrder Order recalculation
     *
     * @return integer
     */
    public function finalizeOrder(Basket $oBasket, $oUser, $blRecalculatingOrder = false)
    {
        $ret = $this->prepareFinalizeOrder($oBasket, $oUser);

        if ($ret !== self::ORDER_STATE_PAYMENTERROR) {
            $ret = parent::finalizeOrder($oBasket, $oUser, $blRecalculatingOrder);
        }

        // Authorize and Capture via Amazon Pay will be done after finalizeOrder in OXID
        // therefore we reset status to "not finished yet"
        if (
            $ret < 2  &&
            !$blRecalculatingOrder &&
            $oBasket->getPaymentId() === Constants::PAYMENT_ID
        ) {
            $this->updateAmazonPayOrderStatus('AMZ_PAYMENT_PENDING');
        }
        return $ret;
    }

    /**
     * If Amazon Pay is active, it will return an address from Amazon
     *
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     *
     * @return \OxidEsales\Eshop\Application\Model\Address|null
     */
    public function getDelAddressInfo()
    {
        $amazonService = $this->getAmazonService();
        $amazonDelAddress = $amazonService->getDeliveryAddress();
        if (
            !$amazonService->isAmazonSessionActive() ||
            !$amazonDelAddress
        ) {
            return parent::getDelAddressInfo();
        }

        $address = oxNew(Address::class);
        $address->assign($amazonDelAddress);

        return $address;
    }

    /**
     * Disabling validation for Amazon addresses when Amazon Pay is active
     *
     * @param $oUser
     *
     * @return int
     */
    public function validateDeliveryAddress($oUser)
    {
        if (!$this->getAmazonService()->isAmazonSessionActive()) {
            return parent::validateDeliveryAddress($oUser);
        }

        return 0; // disable validation
    }

    public function updateAmazonPayOrderStatus(string $amazonPayStatus, $data = null): void
    {
        switch ($amazonPayStatus) {
            case "AMZ_PAYMENT_PENDING":
                $this->oxorder__oxtransstatus = new Field('NOT_FINISHED', Field::T_RAW);
                $this->oxorder__oxtransid = new Field('AMZ_PAYMENT_PENDING', Field::T_RAW);
                $this->oxorder__oxfolder = new Field('ORDERFOLDER_PROBLEMS', Field::T_RAW);
                $this->oxorder__osc_amazon_remark = new Field(
                    'AmazonPay Authorisation pending',
                    Field::T_RAW
                );
                $this->save();
                break;

            case "AMZ_AUTH_STILL_PENDING":
                if (is_array($data)) {
                    $this->oxorder__oxtransid = new Field($data['chargeId'], Field::T_RAW);
                    $this->oxorder__osc_amazon_remark = new Field(
                        'AmazonPay Authorisation still pending: ' . $data['chargeAmount'],
                        Field::T_RAW
                    );
                }
                $this->save();
                break;

            case "AMZ_AUTH_AND_CAPT_FAILED":
                if (is_array($data)) {
                    $response = PhpHelper::jsonToArray($data['result']['response']);
                    if ($data['chargeId']) {
                        $this->oxorder__oxtransid = new Field($data['chargeId'], Field::T_RAW);
                    }
                    $this->oxorder__osc_amazon_remark = new Field(
                        'AmazonPay ERROR: ' . $response['reasonCode'],
                        Field::T_RAW
                    );
                } else {
                    $this->oxorder__osc_amazon_remark = new Field('AmazonPay: ERROR');
                }
                $this->save();
                break;

            case "AMZ_AUTH_AND_CAPT_OK":
                // we move the order only if the oxtransstatus not OK before
                if ($this->getFieldData('oxpaid') == '0000-00-00 00:00:00') {
                    $this->oxorder__oxfolder = new Field('ORDERFOLDER_NEW', Field::T_RAW);
                }
                $this->oxorder__oxpaid = new Field(\date('Y-m-d H:i:s'), Field::T_RAW);
                $this->oxorder__oxtransstatus = new Field('OK', Field::T_RAW);
                if (is_array($data)) {
                    $this->oxorder__oxtransid = new Field($data['chargeId'], Field::T_RAW);
                    $this->oxorder__osc_amazon_remark = new Field(
                        'AmazonPay Captured: ' . $data['chargeAmount'],
                        Field::T_RAW
                    );
                }
                $this->save();
                break;

            case "AMZ_2STEP_AUTH_OK":
                if (is_array($data)) {
                    $this->oxorder__oxtransid = new Field($data['chargeId'], Field::T_RAW);
                    $this->oxorder__osc_amazon_remark = new Field(
                        'AmazonPay Authorized (not Captured):' . $data['chargeAmount'],
                        Field::T_RAW
                    );
                }
                $this->oxorder__oxfolder = new Field('ORDERFOLDER_NEW', Field::T_RAW);
                $this->save();
                break;
        }
    }

    /**
     * Just a helper to allow mock injection for testing
     * @return AmazonService
     */
    public function getAmazonService(): AmazonService
    {
        if (empty($this->amazonService)) {
            $this->setAmazonService(OxidServiceProvider::getAmazonService());
            return $this->amazonService;
        }
        return $this->amazonService;
    }

    /**
     * @param AmazonService $amazonService
     */
    public function setAmazonService(AmazonService $amazonService): void
    {
        $this->amazonService = $amazonService;
    }
}
