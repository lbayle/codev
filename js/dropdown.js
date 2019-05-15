// Dropdown for menu
jQuery(document).ready(function() {

   var submenus = jQuery("ul.dropdown li");

   submenus.click(function(ev){

      if ($(this).hasClass('dropdown-li-opened')) {
         // close current menu
         $(this).removeClass('dropdown-li-opened')
      } else {
         submenus.removeClass('dropdown-li-opened');
         $(this).addClass('dropdown-li-opened');
      }
   });

   jQuery(document).on('click', function(ev) {
      // check if the click is of an element of a dropdown menu
      var $menu = $(ev.target).parents('.dropdown'); 
      if ($menu.length === 0) { 
         submenus.removeClass('dropdown-li-opened'); 
      } 
   }); 

   // la salope de librarie jquery-ui-tabs fait un 'return false' lors du click sur un tab,
   // ce qui annulle le event bubbeling et le mecanisme de fermeture du submenu.
   // => on s'abonne a l'evenenent du changement d'onglet pour simuler le comportement du click...
   jQuery('.tabs').on('tabsselect', function(ev) {
      submenus.removeClass('dropdown-li-opened'); 
   }); 


});
