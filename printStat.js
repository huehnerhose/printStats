(function(){
	
	var printers = ["sozpr1", "sozpr2", "sozgkpr1"];
	var log = {};

	$.ajax({
		url: 'printerStatistics.php',
		type: 'GET',
		dataType: 'json',
	})
	.done(function(data) {
		$.each(printers, function(i, printer) {
			log[printer] = data.filter(function(e){	if(e.printer === printer) return e; });
			$("#head").append("<a href='#' class='printer' id='"+printer+"'>"+printer+"</a>");
			$("a#"+printer).click(function(event) {
				$("a").removeClass('active');
				$(this).addClass('active');
				printerSummary(printer);
				return false;
			});
		});
	});

	function printerSummary(printer){
		var printCount = log[printer].map(function(e){ return parseInt(e.pages) });
		try{
			printCount = printCount.reduce(function(l, c){ return l+c; });	
		}catch(err){
			console.log(err);
			printCount = 0;
		}
		
		var userCount = {};
		$.each(log[printer], function(index, printJob) {
			if(userCount[printJob.user]){
				userCount[printJob.user] += parseInt(printJob.pages, 10);
			}else{
				userCount[printJob.user] = parseInt(printJob.pages, 10);
			}
		});

		var tplUserTable = $("#tpl-printerSummary").html();
		$("#body").html(_.template(tplUserTable, {printsTotal: printCount, userCount: userCount}));
		$(".userTable > table").dataTable();

	}

})();
