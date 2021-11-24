

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






var selectAllModule = (function($){
	let _selectedAll = false;

	function _allSelected(){
		this._selectedAll = true;
	}

	function _allUnSelected(){
		this._selectedAll = false;	
	}

	function _getStatus(){
		return this._selectedAll;
	}
	return {
		setAllSelected: function(){
			return _allSelected()
		},
		setAllUnSelected: function(){
			return _allUnSelected()
		},
		getStatus: function(){
			return _getStatus();
		}
	}
})(jQuery);

$(".btn__select-all").on("click",function(e){
	e.preventDefault();
	if (selectAllModule.getStatus()){
		
		selectAllModule.setAllUnSelected();
		$(".deleted-product-checkbox").prop("checked", false);
	}
	else {
		selectAllModule.setAllSelected();
		$(".deleted-product-checkbox").prop("checked", true);
	}
});

var renderProductDeletedResponse = (data) => {

	console.log(data);
	if (data.error == true){
		$("tr#tr_product_"+data.id).removeClass("product-deleted--processing");
		$("tr#tr_product_"+data.id).addClass("product-deleted--error");
		$("tr#tr_product_"+data.id+" > td.status").html(data.data.msg);
		return 1;
	}
	//Deshabilita el botón 
	if (data.data.status == 1){
		$("tr#tr_product_"+data.id).removeClass("product-deleted--processing");
		$("tr#tr_product_"+data.id).addClass("product-deleted--ok");
		$("tr#tr_product_"+data.id+" > td.status").html(data.data.msg);
		$("tr#tr_product_"+data.id).fadeOut(8000,()=>{});
	}

	if (data.data.status == 2){
		$("tr#tr_product_"+data.id).removeClass("product-deleted--processing");
		$("tr#tr_product_"+data.id).addClass("product-deleted--parcial");
		$("tr#tr_product_"+data.id+" > td.status").html(data.data.msg);
		$("tr#tr_product_"+data.id).fadeOut(12000,()=>{});
	}


	return 1;
}


var sendProductDeletedRequest = (idPrd) => {

	let secToken = $("#csrf-token").prop("value");
	var data = {
		"_token": secToken,
		"id": idPrd,
	};

	$.ajax({
		data: data,
		type: "POST",
		url: '/products/deleted',
		success: function (result) {
			renderProductDeletedResponse(result);
		},
		error: function (xhr, status, error) {
			var err = eval("(" + xhr.responseText + ")");
			console.log(err.error);
		}
	});

}

$("button.btn-delete-one").on("click",function(e){
	e.preventDefault(e);
	let idPrd = this.getAttribute('data-id');
	$("tr#tr_product_"+idPrd).addClass("product-deleted--processing");
	$("tr#tr_product_"+idPrd+" > td.status").html("Procesando...");
	sendProductDeletedRequest(idPrd);
});


$("button.btn-delete-all").on("click",function(e){
	e.preventDefault(e);
	
	$("input.deleted-product-checkbox:checked").each(function(){
		$("tr#tr_product_"+this.getAttribute('value')).addClass("product-deleted--processing");
		$("tr#tr_product_"+this.getAttribute('value')+" > td.status").html("Procesando...");
		sendProductDeletedRequest(this.getAttribute('value'));
	});
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