

$(document).ready(function () {
  /*
   * Permet de gérer la sélection de toutes les ligne 
   */
  
  $('.loginform .btn_connect').click( function (){ 
	$(this).parents('.loginform').find('div.menu').toggleClass("active");
  });

  /* permet de mettre "recherche" dans la zone recherche */
    
    if ($('.champsearch').val() == '')
        $('.champsearch').val('Rechercher...');

    $('.champsearch').focus(function() {
          if ($(this).val() == 'Rechercher...') {
            $(this).val('');
          }
    });
});

