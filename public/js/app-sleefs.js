

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

});


/*

	Event handler to update pics in detailed PO view

*/



$("#btn__updatepics").on("click",function(e){

	e.preventDefault();
	console.log("MMMaaa...");
	updatePicModule.updatePics();
});


var updatePicModule = (function($){

	var updatePicsSkuArr = [];
	var ctrlUpdatePicsProcess = false;
	var ctrlPicsProcessed = 0;

	function _updatePic(){
		var poid = $("td.poid").get(0).getAttribute('data-poid');
		if (ctrlUpdatePicsProcess == false){
			ctrlUpdatePicsProcess = true;
			console.log(poid);
			console.log('Before ========>>>>>>>>>>');
			console.log(updatePicsSkuArr);
			_checkForEmptyPics();
			console.log('After ========<<<<<<<<<<<');
			console.log(updatePicsSkuArr);

			var skuListToShow = '';

			for(var i = 0; i < updatePicsSkuArr.length;i++){

				skuListToShow += '<li data-sku="'+updatePicsSkuArr[i]+'">'+updatePicsSkuArr[i]+'</li>'
				$.ajax({
					url: '/products/updatepic',
					cache: false,
					headers: {
        				'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
    				},
					method: 'PUT',
					data: {
						sku: updatePicsSkuArr[i],
					},
					success: _okUpdatingImage,
					error: _errorUpdatingImage,
				});
			}

			$(".updatepics__console").html("Actualizando las imágenes para los productos:<ul>"+skuListToShow+"</ul>");
			$(".updatepics__console").addClass("msg-displayer--processing");

		}
		else{
			console.log("There is already an update process in progress...")
		}
	}


	function _okUpdatingImage(data){

		console.log("Ok ==========<<<<<<<<< \n");
		console.log(data);

		$("li[data-sku="+data.sku+"]").html($("li[data-sku="+data.sku+"]").html()+" - <span class='msg-displayer-inline--ok'>Ok</span>");
		$("img[data-prdsku="+data.sku+"]").attr("src",data.images[0]);
		$("img[data-prdsku="+data.sku+"]").attr("style","width: 150px;");


		ctrlPicsProcessed++;
		if (ctrlPicsProcessed == updatePicsSkuArr.length){
			_resetValues();
		}

	}


	function _errorUpdatingImage(data){
		console.log("Error ==========>>>>>>>>>>> \n");
		console.log(data);

		$("li[data-sku="+data.responseJSON.sku+"]").html($("li[data-sku="+data.responseJSON.sku+"]").html()+" - <span class='msg-displayer-inline--error'>Error ("+data.responseJSON.message+")</span>");

		ctrlPicsProcessed++;
		if (ctrlPicsProcessed == updatePicsSkuArr.length){
			_resetValues();
		}
	}


	function _resetValues(){

		var totalTimeOut = 1500 * parseInt(updatePicsSkuArr.length);// 1.5 segundos por producto
		if (totalTimeOut < 3000){
			totalTimeOut = 3000;
		}
		setTimeout(function(){

			updatePicsSkuArr = [];
			ctrlUpdatePicsProcess = false;
			ctrlPicsProcessed = 0;

			$(".updatepics__console").html("");
			$(".updatepics__console").removeClass("msg-displayer--processing");

		},totalTimeOut);
	}


	function _checkForEmptyPics(){

		var imgs = $("img.product-img");
		var regexpImg = /(no\-image)/i;
		imgs.each(function(index,imgObject){
			if (regexpImg.exec(imgObject.src)){
				updatePicsSkuArr.push(imgObject.getAttribute('data-prdsku'));
			}
		});

	}

	return {
		updatePics : function(){
			_updatePic();
		},
	} 

})(jQuery);