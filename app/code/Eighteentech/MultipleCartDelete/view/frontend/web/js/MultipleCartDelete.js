/**
 * MultipleCartDelete JS
 *
 * @author 18th Digitech <info@18thdigitech.com>
 * @package Eighteentech_MultipleCartDelete
 */
 define([
    'jquery'
], function($){
   "use strict";
      $.widget('mage.MultipleCartDelete', {
        options: {
            selectAll: '#select_all',
        },
        
        _create: function () {
            var self = this;
			var values=[];

            $(".selectAll").click(function () {
                $('.checkBoxClass').attr('checked', this.checked);
                var checkboxcount = $('.cart.item').find('input[type=checkbox]:checked').length;
                $('#count-checked-checkboxes').html(checkboxcount);
            });

            $(".checkBoxClass").click(function(){
                if($(".checkBoxClass").length == $(".checkBoxClass:checked").length) {
                    $(".selectAll").attr("checked", "checked");
                } else {
                    $(".selectAll").removeAttr("checked");
                }
                var checkboxcount = $('.cart.item').find('input[type=checkbox]:checked').length;
                $('#count-checked-checkboxes').html(checkboxcount);
            });

            $(".deleteAll").click(function () {
                if ($(".checkBoxClass:checked").length > 0)
                {
                    $(".form-cart").submit();
                }else{
                    alert("Please checked checkbox");
                }

            });
         },
        
        /**
         * Initialize configuration.
         * @private
         */
        _initializeWidget: function () {
            var options = this.options;
        },
       
    });
    return $.mage.MultipleCartDelete;
});