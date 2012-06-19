###TEMPLATE###
<script type="text/javascript">

$(document).ready(function () {
	console.log('js contact');
        $( ".contact").each(function() { 
			/* Templavoila modifie les liens et fait planter colorbox
                         * On a : a href ="notre_pahe.html#contact
			 * Et on attend a href ="#tabs-1 
			 */
 			
			var url = $(this).attr('href');
		        // on ne garde que l'ancre 
		        url = url.substring(url.indexOf('#'));
			$(this).attr('href',url);
		});
                
        /* initilisation de colorbox */        
		$('.contact').colorbox({
			inline:true, 
			onComplete:function(){
				initContact();
			}
		});
		/*
		$('.contact').click(function () {
			console.log('click contact');
			return false;
		    // Permet d'initialiser les champs cachés des formulaire
			var formName = $(this).attr('href'); // ex: #contact_default, #contact_panier, #contact_revendeur ...
		    // on ne garde que l'ancre 
		    formName = formName.substring(formName.indexOf('#'));

		    var zone = $(this).attr('name');
		    $(formName + ' input[name$="zone"]').val(zone);
		    $(formName+' input[name$="whatId"]').val($('.pack-content').attr('id'));
		    
		});
		
		*/
		/* Permet de valider le formulaire en ajax */
		$('.contact_formulaire form').submit(function(){validation(this); return false;});
		
});

/*  Appel à validation d'un formulaire contact
 * @param formulaire 
 * 
 */
function validation (form) {
	
	/* permet de sérialiser le formulaire */
	var postData = $(form).serialize();
    
    /* sauvegarde des cookies */
    $(form).find('input').each(function(x) {
        var info =  $(this).attr("name");       
        var valeur =  $(this).attr("value");    
        if ((valeur != '') && 
        		(info == 'LastName' || info == 'FirstName' || info == 'Telephone' || info == 'Email')) {
        	createCookie($(this).attr("name"), valeur, 5);
                
        }

    });
    
    // message d'attente 
    $(form).toggleClass('wait');
    $(form).find('input[type=submit]').attr({'disabled': 'disabled','value' : '###WAIT###'});
    
    $.ajax({
        url: "###URL###",
        type:"POST",
        data: postData,
        dataType: "html",
        success: function(data) {
        	console.log('retour ajax');
        	/* il faut retrouver l'id du formulaire  <div id="info"> <div class>... <form> */
        	id = ($(form).parents('.ajax-replace-content').parent('div')).attr('id');
        	console.log('retour ajax' + id);
            var classReplace = "ajax-replace-content";
            var divReplace = "#" + id + " ." + classReplace;
            var newContent = '<div class="' + classReplace + '">' + $(divReplace, data).html(); + '</div>';
            $(divReplace).replaceWith(newContent);
            /* retaille de la fenêtre */
            $.colorbox.resize();
            /* permet de rajouter l'action ajaxcontact à la validation du formulaire */
            $('#' + id + ' form').submit(function(){validation(this); return false;});
        }
                
    });
    // pour eviter l'envoie du formulaire
    return false;

};

function initContact () {
	/* lecture des cookies */
	console.log('readCookie');
	$('#cboxContent').find('input').each(function(x) {
		var info =  $(this).attr("name");   
		var valeur = readCookie(info);
		console.log('readCookie : ' + info  + ' -> ' + valeur);
		if (valeur != null && $(this).val(valeur) == '') {
			$(this).val(valeur);
		}

	});
	
};


</script>
###TEMPLATE###
