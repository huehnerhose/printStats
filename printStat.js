(function(){
	
	var printers = ["all", "sozpr1", "sozpr2", "sozgkpr1"];
	var years = [];
	var log = {};
	var filterYear, filterMonth;

	$.ajax({
		url: 'printerStatistics.php',
		type: 'GET',
		dataType: 'json',
	})
	.done(function(data) {
		$.each(printers, function(i, printer) {
			log[printer] = data.filter(function(e){	if(e.printer === printer) return e; });
			$("#head #printer").append("<a href='#' class='printer' id='"+printer+"'>"+printer+"</a>");
			$("a#"+printer).click(function(event) {
				$("a").removeClass('active');
				$(this).addClass('active');
				printerSummary($("a.printer.active").html(), $("a.year.active").html(), $("a.month.active").html());
				return false;
			});
		});

		years = getLoggedYears(log);

		$.each(years, function(i, year){
			$('#year').append("<a href='#' class='year' id='y"+year+"'>"+year+"</a>");
			$("#y"+year).click(function(){
				$("a.year").removeClass("active");
				$(this).addClass('active');
				return false;
			})
		});

		for(var month = 1; month <= 12; month++){
			$('#month').append("<a href='#' class='month' id='m"+month+"'>"+month+"</a>");
			$("#m"+month).click(function(){
				$("a.month").removeClass("active");
				$(this).addClass('active');
				printerSummary($("a.printer.active").html(), $("a.year.active").html(), $("a.month.active").html());
				return false;
			})
		};

	});

	function getLoggedYears(log){
		var years = [];
		$.each(printers, function(i, printer){
			$.each(log[printer], function(i, e){
				if($.inArray(e.date.substring(0,4), years) == -1)
					years.push(e.date.substring(0,4));
			})
		});
		return years;
	}

	function filterLog(printer, year, month){
		if(year == null || month == null){
			console.log("no date");
			return log[printer];
		}

		if(month.length < 2)
			month = "0"+month;

		var filteredLog = log[printer].filter(function(j){
			if(j.date.indexOf(year+"-"+month) != -1)
				return j;
		});

		return filteredLog;
	}

	function getPrintCount(printer, year, month){
		var log = filterLog(printer, year, month);
		var printCount = log.map(function(e){ 
			return parseInt(e.pages);
		});
		try{
			printCount = printCount.reduce(function(l, c){ return l+c; });	
		}catch(err){
			console.log(err);
			printCount = 0;
		}

		return printCount;

	}

	function getUserCount(printer, year, month, obj){
		var log = filterLog(printer, year, month);
		var userCount;
		if(typeof(obj) === "object"){
			userCount = obj;
		}else{
			userCount = {};
		}

		$.each(log, function(index, printJob) {
			if(userCount[printJob.user]){
				userCount[printJob.user] += parseInt(printJob.pages, 10);
			}else{
				userCount[printJob.user] = parseInt(printJob.pages, 10);
			}
		});
		return userCount;
	}

	function printerSummary(printer, year, month){
		var printCount = 0;
		var userCount;
		if(printer != "all"){
			printCount = getPrintCount(printer, year, month);
			userCount = getUserCount(printer, year, month);
		}else{
			$.each(printers, function(index, printer){
				if(printer != "all"){
					printCount += getPrintCount(printer, year, month);
					userCount = getUserCount(printer, year, month, userCount);
				}
			})
		}

		
		var tplUserTable = $("#tpl-printerSummary").html();
		$("#body").html(_.template(tplUserTable, {printsTotal: printCount, userCount: userCount}));
		$(".userTable > table").dataTable();

	}

})();
