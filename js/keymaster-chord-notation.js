jQuery(function($){
    if(typeof KCNhasChords==='undefined'){
	$('.KCNtranspose').hide();
    }
    $('.KCNshowHide').click(function(){
	$('.KCNchordWrap').toggle('slow');
    });
    $('.KCNprint').click(function() {
	var title = document.title;
	$(KCN_print_selector).printArea({popTitle:title,mode:'popup'});
	return false;
    });
});
