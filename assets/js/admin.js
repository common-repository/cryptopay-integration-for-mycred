;(($) => {

    $(document).ready(() => {
        const helpers = window.cpHelpers;
        const app = window.CryptoPayApp;
        const lang = window.CryptoPayLang;
        const payNowFunction = $._data($("#cashcred_paynow")[0] || $(), "events").click[0]?.handler;

        const ourPayFunction = function () {
            form 		= 	jQuery('#post');
            btn_paynow	=	jQuery(this);
            placeholder =	jQuery('#placeholder');
            spinner		=	jQuery('#cashcred-payment-status .spinner');
            
            var data = jQuery(form).serialize() + "&action=cashcred_pay_now";

            jQuery.ajax({
                type: 'POST',
                url: mycredCryptoPay.ajaxUrl,
                data: data,
                dataType: "json",
                beforeSend: function() {
                    
                    // setting a timeout
                    jQuery(spinner).addClass('is-active');
                    jQuery('.cashcred_paynow_text').text('Loading...');
                    jQuery('#payment_response').slideUp();
                    jQuery(btn_paynow).prop('disabled', true);
                    jQuery( '#payment_response' ).removeClass();
                    
                },
                success: function( response ) {
                        
                    jQuery( '.cashcred_paynow_text' ).text('Pay Now');
                    jQuery( '#payment_response' ).html(response.message);
                    jQuery( '#payment_response' ).addClass(""+ response.status +"");
                    jQuery( '#payment_response' ).slideDown();
                    
                    if(response.status == true){
                        jQuery( '.disabled_fields' ).prop('disabled', true);
                        jQuery( '.readonly_fields' ).prop('readonly', true);
                        jQuery( btn_paynow ).prop('disabled', true);
                        jQuery('.cashcred_Approved').remove();
                        
                        html_approved = "<span class='cashcred_Approved'>Approved</span>";
                        comments = "<li><time>"+response.date+"</time><p>"+response.comments+"</p></li>";
                        
                        jQuery( '#cashcred-comments .history').prepend(comments);
                        jQuery('.type-cashcred_withdrawal .form-group').html(html_approved);
                        jQuery( '#cashcred-payment-status .entry-date' ).html(response.date); 
                        jQuery('#cashcred_post_ststus select').get(0).selectedIndex = 1;
                        jQuery( '#user_total_cashcred' ).html(response.total); 

                    }else{
                        jQuery( btn_paynow ).prop('disabled', false);
                    }
                    
                    
                    if( jQuery('#cashcred-developer-log').length && response.log != null ) {
                    
                        jQuery( '#cashcred-developer-log .inside' ).html( response.log ); 
                    
                    }
                    
                },
                error: function(xhr) {
                
                    // if error occured
                    alert("Error occured.please try again");

                    jQuery( '#payment_response' ).html(xhr.responseText);
                    jQuery( '#payment_response' ).addClass('false');					
                    jQuery( btn_paynow ).prop('disabled', false);
                    jQuery( '.cashcred_paynow_text' ).text('Pay Now');
                },
                complete: function() {
                    
                    jQuery(spinner).removeClass('is-active');
                    
                }
            });
        }

        if (payNowFunction) {

            let alreadyPaid = false;
            app.events.add('transactionReceived', ({transaction}) => {
                helpers.successPopup(lang.transactionSent, `
                    <a href="${transaction.getUrl()}" target="_blank">
                        ${lang.openInExplorer}
                    </a>
                `).then(() => {
                    app.modal.close();
                });
                ourPayFunction();
                alreadyPaid = true;
            });

            $("#cashcred_paynow").off("click", payNowFunction);
            $("#cashcred_paynow").on('click', function (e) {
                if (alreadyPaid) {
                    return;
                }
                const selectedGateway = $("#cashcred-pending-payment-gateway").val();
                if (selectedGateway !== 'cryptopay') {
                    return payNowFunction(e);
                }

                app.modal.open();
            });
        }
    });

})(jQuery);