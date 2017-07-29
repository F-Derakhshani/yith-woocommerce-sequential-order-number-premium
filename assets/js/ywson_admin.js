/**
 * Created by Your Inspiration on 29/04/2015.
 */
jQuery( document ).ready( function( $ ) {

  $('#ywson_check_custom_free_order').on('change', function(){
      var t = $(this),
          free_order_numeration =   $('#ywson_start_free_order_number'),
          free_prefix           =   $('#ywson_free_order_prefix'),
          free_suffix           =   $('#ywson_free_order_suffix'),
          free_type             =   $('#ywson_free_order_type');


      if( t.is(':checked') ){
          free_order_numeration.parents( 'tr').show();
          free_prefix.parents('tr').show();
          free_suffix.parents('tr').show();
          free_type.parents('tr').show();
      }
      else{
          free_order_numeration.parents( 'tr').hide();
          free_prefix.parents('tr').hide();
          free_suffix.parents('tr').hide();
          free_type.parents('tr').hide();
      }
  }).change();

    $('.ywson_hide_option').parents('tr').hide();
    
    $('#ywson_check_custom_quote_order').on('change',function() {
    
        var t = $(this),
            is_disabled = t.prop('disabled') ;
        
        if( !is_disabled ){
            
            var quote_order_numeration =   $('#ywson_start_quote_order_number'),
                quote_prefix           =   $('#ywson_quote_order_prefix'),
                quote_suffix           =   $('#ywson_quote_order_suffix');

            if( t.is(':checked')){
                quote_order_numeration.parents('tr').show();
                quote_prefix.parents('tr').show();
                quote_suffix.parents('tr').show();
            }else{
                quote_order_numeration.parents('tr').hide();
                quote_prefix.parents('tr').hide();
                quote_suffix.parents('tr').hide();
            }
        }
        
    }).change();
} );