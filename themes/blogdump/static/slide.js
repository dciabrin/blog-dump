/* Slide effect from https://github.com/apeatling/smooth-mobile-menu */

( function( $ ) {
 /*** 
  * Run this code when the #toggle-menu link has been tapped
  * or clicked
  */

    /* dciabrin: I cheat here: I just move the menu to another DOM
     * position to implement the hidden menu slide on mobile and 
     * small screens :P
     */
    if ($('#toggle-menu').css('display') != 'none') {
        $('#body').prepend($( '#menu' ));
    }
    
$( '#toggle-menu' ).on( 'touchstart click', function(e) {
  e.preventDefault();
  var $body = $( 'body' ),
      $page = $( '#page' ),
      $menu = $( '#menu' ),
 
      /* Cross browser support for CSS "transition end" event */
      transitionEnd = 'transitionend webkitTransitionEnd otransitionend MSTransitionEnd';
 
  /* When the toggle menu link is clicked, animation starts */
  $body.addClass( 'animating' );
 
  /***
   * Determine the direction of the animation and
   * add the correct direction class depending
   * on whether the menu was already visible.
   */
  if ( $body.hasClass( 'menu-visible' ) ) {
   $body.addClass( 'toright' );
  } else {
   $body.addClass( 'toleft' );
  }
  
  /***
   * When the animation (technically a CSS transition)
   * has finished, remove all animating classes and
   * either add or remove the "menu-visible" class 
   * depending whether it was visible or not previously.
   */
  $page.on( transitionEnd, function() {
      console.log("animation ended!");

      $body
    .removeClass( 'animating toleft toright' )
    .toggleClass( 'menu-visible' );
 
   $page.off( transitionEnd );
  } );
 } );
} )( jQuery );
