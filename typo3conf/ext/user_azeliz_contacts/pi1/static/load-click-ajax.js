###TEMPLATE###
<script type="text/javascript">
$(document).ready(function () {
    $('.contact').colorbox({inline:true,
			   onComplete:function(){openContact();}
			   });
    //$('.headercontact').colorbox({inline:true ###erreur###});

});

function openContact () {
	var nom_formulaire = $('form:visible').attr('name');
	$('form:visible').submit(
		function(){
			ajaxcontact($(this).attr('name'));
			// permet de ne pas déclancher le fonctionnement normal du formulaire
			return false;
		});
}

$('.contact').click(function () {
    var formName = $(this).attr('href'); // ex: #contact_default, #contact_panier, #contact_revendeur ...
    
    switch(formName) {
        case "#contact_panier" :
            var zone = $(this).attr('name');
            $(formName+' input[name$="zone"]').val(zone);
            
            var titre = $("title").text().split('-');
            var objet = "Cette offre m\'intéresse : " + $.trim(titre[0]) + " - " + zone;
            $(formName+' input[name$="Objet"]').val(objet);
            break;
        
        case "#contact_ensavoirplus" :
            var zone = $(this).attr('name');
            $(formName+' input[name$="zone"]').val(zone);
            
            var titre = $("title").text().split('-');
            var objet = "En savoir plus : " + $.trim(titre[0]) + " - " + zone;
            $(formName+' input[name$="Objet"]').val(objet);
            break;
        
        case "#contact_pdf" :
            var titre = $("title").text().split('-');
            $(formName+' input[name$="Objet"]').val("Demande de PDF : " + $.trim(titre[0]));
            break;
    
        case "#contact_revendeur" :
            var titre = $("title").text().split('-');
            $(formName+' input[name$="Objet"]').val("Trouvez un revendeur : " + $.trim(titre[0]));
            break;
    }
    $(formName+' input[name$="whatId"]').val($('.pack-content').attr('id'));

    /* lecture des cookies */
    $(formName+' input').each(function(x) {
        var info =  $(this).attr("name");       
	var valeur = readCookie(info);
	if (valeur != null && $(this).val(valeur) == '') {
		$(this).val(valeur);
	}

    });

    //alert("Id Salesforce : " + $(formName+' input[name$="whatId"]').attr("value"));
    //alert("Zone : " + $(formName+' input[name$="zone"]').attr("value"));
    //alert("Titre : " + $("title").text() + " | Titre[0] : " + titre[0]);
    //alert("Objet : " + $(formName+' input[name$="Objet"]').attr("value"));
    // Ajout de l'object
    if (formName != '#contact_default') {
        
    }
});

function ajaxcontact(form) {
    //event.preventDefault();
    var formselect = "form[name=" + form + "] :input";
    var postData = "";

    $(formselect).each(function(x) {
        if (postData != "") {
            postData += "&";
        }
        var info =  $(this).attr("name");       
	postData += $(this).attr("name") + "=" + $(this).attr("value");

    });
    /* sauvegarde des cookies */
    $(formselect).each(function(x) {
        var info =  $(this).attr("name");       
        var valeur =  $(this).attr("value");    
	if ((valeur != '') && 
	    (info == 'LastName' || info == 'FirstName' || info == 'Telephone' || info == 'Email')) {
	   createCookie($(this).attr("name"), valeur, 5);
	}

    });


    $.ajax({
        url: "###URL###",
        type:"POST",
        data: postData,
        dataType: "html",
        success: function(data) {
            var classReplace = "ajax-replace-content";
            var divReplace = "#" + form + " ." + classReplace;
            var newContent = '<div class="' + classReplace + '">' + $(divReplace, data).html(); + '</div>';
            $(divReplace).replaceWith(newContent);
            /* retaille de la fenêtre */
	    $.colorbox.resize();
 	    /* permet de rajouter l'action ajaxcontact à la validation du formulaire */
	    openContact ();
        }
    });

};
</script>
###TEMPLATE###
