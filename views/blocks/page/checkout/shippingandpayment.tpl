[{if $oViewConf->isAmazonActive() && $oViewConf->isAmazonSessionActive() && !$oViewConf->isAmazonPaymentPossible()}]
    [{if $oViewConf->isFlowCompatibleTheme()}]
        [{include file="amazonpay/shippingandpayment_flow.tpl"}]
    [{else}]
        [{include file="amazonpay/shippingandpayment_wave.tpl"}]
    [{/if}]
[{else}]
    [{$smarty.block.parent}]
[{/if}]