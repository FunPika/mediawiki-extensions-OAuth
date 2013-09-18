/**
 * OAuth JavaScript
 * @author Aaron Schulz 2013
 */
( function( mw, $ ) {
	"use strict";

	var mwoauth = {
		'init' : function () {
			var form = $( '#mw-mwoauth-authorize-dialog' );
			form.find( '.mw-htmlform-submit-buttons' ).addClass( 'mw-ui-flush-right' );
			form.dialog( {
				modal: true,
				width: 800,
				maxHeight: 600,
				title: mw.msg( 'mwoauth-desc' )
			} );
		}
	};

// Perform some onload events:
$( document ).ready( function () {
	mwoauth.init();
} );

})( mediaWiki, jQuery );