IWD.OPC.Decorator.updateGrandTotal = IWD.OPC.Decorator.updateGrandTotal.wrap(function (updateGrandTotal, response) {
    $j_opc('.opc-review-actions h5 span').html(response.grandTotal);
    $j_opc('.review-total span').html(response.grandTotal);
    IWD.OPC.Decorator.manageVisibleOfTaxSection();
    IWD.OPC.Decorator.showQuoteTaxInfo();
})

IWD.OPC.Decorator.showQuoteTaxInfo = function () {
    $j_opc.post(IWD.OPC.Checkout.config.baseUrl + 'onepage/json/getQuoteTax', function(data) {
        $j_opc('#calculate-tax').html(data + " %");
    });
}

IWD.OPC.Decorator.manageVisibleOfTaxSection = function () {
    $j_opc.post(IWD.OPC.Checkout.config.baseUrl + 'onepage/json/checkIsDefaultAddressUsed', function(data) {
        if (data) {
            $j_opc('#tax-info').hide();
        } else {
            $j_opc('#tax-info').show();
        }
    });
}
