var host = window.location.host; 

if(typeof host !== "undefined" && host == '127.0.0.1')
{
    var API_URL = 'http://127.0.0.1/merchant_money/merchants.money/api/v1_1/api';
}
else
{
    var API_URL = 'https://merchants.money/api/v1_1/api';
}


jQuery(document).ready(function($) 
{    
    $(document).on('click','#send_verification_code_mu',function()
    {
        var merchant_phone_number_mu = $('#merchant_phone_number_mu').val();
        if(merchant_phone_number_mu == '')
        {
            $('#merchant_phone_number_mu_error').show();
        }
        else
        {
            $('#merchant_phone_number_mu_error').hide();
            
            var number = { merchant_phone_number: merchant_phone_number_mu };
            
            $.ajax({
            url: API_URL + '/request_otp_transaction',
            headers: {
                'X-Merchant-Id':$('#mu_merchant_id_value').val(),
                'X-Merchant-Host':window.location.hostname
            },
            method: 'POST',
            dataType: 'json',
            data: JSON.stringify(number),
            success: function(data)
            {
                if(data.SUCCESS == 0)
                {
                    $('#merchant_phone_number_mu_error').text(data.MESSAGE);
                    $('#merchant_phone_number_mu_error').show();
                    $('#merchant_phone_number_mu_success').hide();
                }
                else
                {
                    $('#merchant_phone_number_mu_error').hide();
                    $('#merchant_phone_number_mu_success').show();
                }                
            }
          });          
        }
    });
    
    $(document).on('click','#verify_code_mu',function()
    {
        var merchant_phone_number_mu = $('#merchant_phone_number_mu').val();
        
        if(merchant_phone_number_mu == '')
        {
            $('#merchant_phone_number_mu_error').show();
        }else
        {
            $('#merchant_phone_number_mu_error').hide();
        }
        
        var merchant_sms_code_mu = $('#merchant_sms_code_mu').val();
        if(merchant_sms_code_mu == '')
        {
            $('#merchant_sms_code_mu_error').show();
        }
        else
        {            
            $('#merchant_sms_code_mu_error').hide();
            
             var number = { merchant_phone_number: merchant_phone_number_mu, verification_code : merchant_sms_code_mu };
            
            $.ajax({
            url: API_URL + '/verify_otp_transaction',
            headers: {
                'X-Merchant-Id':$('#mu_merchant_id_value').val(),
                'X-Merchant-Host':window.location.hostname
            },
            method: 'POST',
            dataType: 'json',
            data: JSON.stringify(number),
            success: function(data)
            {
                if(data.SUCCESS == 0)
                {
                    $('#merchant_sms_code_mu_error').text(data.MESSAGE);
                    $('#merchant_sms_code_mu_error').show();
                }
                else
                {
                    $('#merchant_sms_success_id_mu').val(data.DATA.success_id);                    
                    $('#merchant_sms_code_mu_error').hide();
                    $('#merchant_sms_code_mu_success').show();
                    
                    $('#show_details').hide('slow');
                    $('#member_buttons').hide('slow');
                    $('#show_success_message').show();
                }               
            }
          });
          
        }
    });
});