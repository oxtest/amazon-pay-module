<div class="row">
    <div class="col-xs-12">
        <form action="[{$oViewConf->getSslSelfLink()}]" method="post">
            <div class="hidden">
                [{$oViewConf->getHiddenSid()}]
                <input type="hidden" name="cl" value="payment">
                <input type="hidden" name="fnc" value="">
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        [{oxmultilang ident="AMAZON_PAY_CHECKOUT_ERROR_HEAD"}]
                        <button type="submit" class="btn btn-xs btn-warning pull-right submitButton largeButton" title="[{oxmultilang ident="EDIT"}]">
                            <i class="fa fa-pencil"></i>
                        </button>
                    </h3>
                </div>
                <div class="panel-body">
                    <p class="alert alert-danger">
                        [{oxmultilang ident="AMAZON_PAY_CHECKOUT_ERROR"}]
                    </p>
                    <a href="#" class="btn btn-warning" onclick="$('#amznChangeAddress')[0].click(); return false;">
                        [{oxmultilang ident="AMAZON_PAY_CHECKOUT_CHANGE_ADDRESS"}]
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>