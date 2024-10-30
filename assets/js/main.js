;(($) => {
    $(document).ready(() => {
        $(document).on('change', '.mycred-cryptopay-network', function(e) {
            let currencies = JSON.parse($(this).val()).currencies;
            $('.mycred-cryptopay-currency').html(`
                ${currencies.map(currency => `<option value='${JSON.stringify(currency)}'>${currency.symbol}</option>`).join('')}
            `);
        });
    })
})(jQuery);