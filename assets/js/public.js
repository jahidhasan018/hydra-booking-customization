'use strict';

jQuery(document).ready(function($){
    // Pre populate email and name to booking form if user logged in
    if( hbc_user_info ){
        let name = hbc_user_info.username;
        let email = hbc_user_info.email;

        // Fields
        // $('#email').attr('disabled', 'disabled');
        // $('#email').css('pointer-events', 'none');
        $('#name').val(name);
        $('#email').attr('value', email);
        $('#email').removeAttr('required');
        $('.tfhb-single-form label[for="email"]').text('Email');
    }
});
