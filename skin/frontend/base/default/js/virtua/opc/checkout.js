IWD.OPC.Decorator.updateGrandTotal = IWD.OPC.Decorator.updateGrandTotal.wrap(function (updateGrandTotal, response) {
    $j_opc('.opc-review-actions h5 span').html(response.grandTotal);
    $j_opc('.review-total span').html(response.grandTotal);
    IWD.OPC.Decorator.showQuoteTaxInfo();
    IWD.OPC.Decorator.getCustomerVatValidationResults();
})

IWD.OPC.Decorator.showQuoteTaxInfo = function () {
    $j_opc.post(IWD.OPC.Checkout.config.baseUrl + 'onepage/json/getQuoteTax', function(data) {
        $j_opc('#calculate-tax').html(data + " %");
        $j_opc('#tax-info').show();
    });
}

IWD.OPC.Decorator.getCustomerVatValidationResults = function () {
    $j_opc.post(IWD.OPC.Checkout.config.baseUrl + 'onepage/json/getCustomerVatValidationResults', function(data) {
        if (data == 3) {
            $j_opc('#calculate-tax-info').hide();
            $j_opc('#domestic-shipping-info').show();
        } else {
            $j_opc('#domestic-shipping-info').hide();
            $j_opc('#calculate-tax-info').show();
        }
    });
}
