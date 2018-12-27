

$("a.poupdate__items__displayer").on('click',function(e){

	e.preventDefault();
	let idPoUpdate = $(this).attr('data-poupdate');
	//console.log(idPoUpdate);
	let trPoUpdateItemsIdentifier = 'update__tr__items__'+idPoUpdate;
	$("."+trPoUpdateItemsIdentifier).toggle();
	if ($(e.currentTarget).attr('data-status') == 'open'){
		$(e.currentTarget).find("span").removeClass('icon-arrow-down').addClass('icon-arrow-right');
		$(e.currentTarget).attr('data-status','close');
	}
	else{
		$(e.currentTarget).find("span").removeClass('icon-arrow-right').addClass('icon-arrow-down');
		$(e.currentTarget).attr('data-status','open');
	}
	return 1;

});


$(".updateitems__selector").on('click',function(e){

	let idPoUpdate = $(this).attr('data-poupdate');
	console.log(idPoUpdate);
	console.log($(e.currentTarget).is(':checked'));
	if ($(e.currentTarget).is(':checked')){
		$(".itemUpdate_"+idPoUpdate).each(function(index){
			//$(this).attr('checked', 'checked');
			$(this).prop('checked', true);
		});
	}
	else{
		$(".itemUpdate_"+idPoUpdate).each(function(index){
			//$(this).removeAttr("checked");
			$(this).prop('checked', false);
		});
	}
	return 1;

});



$(".btn-report").on("click",function(e){

	//e.preventDefault();
	//let idlist = '';
	//$(".itemUpdateItemInput").map()

})