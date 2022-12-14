<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\AmazonPay\Tests\Codeception\Acceptance;

use Codeception\Util\Fixtures;
use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Step\Basket as BasketSteps;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\AmazonPay\Tests\Codeception\AcceptanceTester;
use OxidSolutionCatalysts\AmazonPay\Tests\Codeception\Page\AcceptSSLCertificate;
use OxidSolutionCatalysts\AmazonPay\Tests\Codeception\Page\AmazonPayInformation;
use OxidSolutionCatalysts\AmazonPay\Tests\Codeception\Page\AmazonPayLogin;

abstract class BaseCest
{
    private int $amount = 1;
    private AcceptanceTester $I;

    public function _before(AcceptanceTester $I): void
    {
        $I->haveInDatabase(
            'oxobject2payment',
            [
                'OXID' => 'testAmazonPay',
                'OXOBJECTID' => 'oxidstandard',
                'OXPAYMENTID' => 'oxidamazon',
                'OXTYPE' => 'oxcountry'
            ]
        );

        $this->I = $I;
    }

    public function _after(AcceptanceTester $I): void
    {
        $I->clearShopCache();
        $I->cleanUp();
    }

    /**
     * @return void
     */
    protected function _initializeTest()
    {
        $this->I->openShop();

        $this->I->wait(5);
        $acceptCertificatePage = new AcceptSSLCertificate($this->I);
        $acceptCertificatePage->acceptCertificate();
    }

    /**
     * @return void
     */
    protected function _addProductToBasket()
    {
        $basketItem = Fixtures::get('product');
        $basketSteps = new BasketSteps($this->I);
        $basketSteps->addProductToBasket($basketItem['id'], $this->amount);
    }

    protected function _openDetailPage()
    {
        $this->I->waitForText(Translator::translate('MORE_INFO'));
        $this->I->click(Translator::translate('MORE_INFO'));
    }

    /**
     * @return void
     */
    protected function _loginOxid()
    {
        $homePage = $this->I->openShop();
        $clientData = Fixtures::get('client');
        $homePage->loginUser($clientData['username'], $clientData['password']);
    }

    protected function _loginOxidWithAmazonCredentials()
    {
        $loginInput = "//input[@name='lgn_usr' and ".
            "@class='form-control textbox js-oxValidate js-oxValidate_notEmpty']";
        $passwordInput = "//input[@name='lgn_pwd' and ".
            "@class='form-control js-oxValidate js-oxValidate_notEmpty textbox stepsPasswordbox']";
        $loginButton = "//button[@class='btn btn-primary submitButton']";
        $continueButton = "//button[@id='userNextStepTop']";

        $this->I->waitForPageLoad();
        $this->I->waitForElement($loginInput);
        $this->I->fillField($loginInput, Fixtures::get('amazonClientUsername'));
        $this->I->fillField($passwordInput, Fixtures::get('amazonClientPassword'));
        $this->I->click($loginButton);

        $this->I->waitForPageLoad();
        $this->I->waitForElement($continueButton);
        $this->I->clickWithLeftButton($continueButton);
    }

    /**
     * @return void
     */
    protected function _openCheckout()
    {
        $homePage = $this->I->openShop();
        $homePage->openMiniBasket()->openCheckout();
    }

    /**
     * @return void
     */
    protected function _openBasketDisplay()
    {
        $homePage = $this->I->openShop();
        $homePage->openMiniBasket()->openBasketDisplay();
    }

    /**
     * @return void
     */
    protected function _openAmazonPayPage()
    {
        $amazonpayDiv = "//div[contains(@id, 'AmazonPayButton')]";

        $this->I->waitForElement($amazonpayDiv);
        $this->I->click($amazonpayDiv);
    }

    /**
     * @return void
     */
    protected function _loginAmazonPayment()
    {
        $amazonpayLoginPage = new AmazonPayLogin($this->I);
        $amazonpayLoginPage->login();
    }

    /**
     * @return void
     */
    protected function _checkAccountExist()
    {
        $this->I->waitForDocumentReadyState();
        $this->I->waitForText(strip_tags(sprintf(
            Translator::translate('AMAZON_PAY_USEREXISTS'),
            Fixtures::get('amazonClientUsername'),
            Fixtures::get('amazonClientUsername')
        )));
    }

    /**
     * @return void
     */
    protected function _submitPaymentMethod()
    {
        $amazonpayInformationPage = new AmazonPayInformation($this->I);
        $amazonpayInformationPage->submitPayment();
    }

    /**
     * @return void
     */
    protected function _cancelPeyment()
    {
        $amazonpayInformationPage = new AmazonPayInformation($this->I);
        $amazonpayInformationPage->cancelPayment();
    }

    /**
     * @return void
     */
    protected function _submitOrder()
    {
        $this->I->waitForText(Translator::translate('SUBMIT_ORDER'));
        $this->I->click(Translator::translate('SUBMIT_ORDER'));
    }

    /**
     * @return void
     */
    protected function _checkSuccessfulPayment()
    {
        $this->I->waitForDocumentReadyState();
        $this->I->waitForText(Translator::translate('THANK_YOU'));
    }
}
