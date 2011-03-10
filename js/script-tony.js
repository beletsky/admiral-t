$(document).ready( function() {

	$("#h-icons li").append("<span></span>");
	$("#h-icons li").each(function() {
		var hoverText = $(this).find("img").attr("alt");
		$(this).find("span").text(hoverText);
	});
	$("#h-icons li").hover( function() {
		$(this).find("span").show();
	 }, function () {
		$(this).find("span").hide();
	 });
 
 
});


		

