
/**
 * Model for each printJob. 
 * Customset function takes care of handling a proper date object as date.
 */
var Model_RawData = Backbone.Model.extend({
	
	set: function(attributes, properties){

		if(!_.isUndefined( attributes.date ) && typeof( attributes.date ) != "object" ){
			var strDate = attributes.date;
			var date = new Date(strDate.replace(" ", "T"));

			attributes.day = date.getDate();
			attributes.month = date.getMonth();
			attributes.year = date.getFullYear();

			attributes.date = date;
		}

		attributes.jobid = parseInt(attributes.jobid, 10);
		attributes.pages = parseInt(attributes.pages, 10);

		attributes.costcenter = statisticsApp.user.getCCbyUser(attributes.user);

		Backbone.Model.prototype.set.call(this, attributes, properties);
	}
});

/**
 * Collection of PrintJobModels
 * Takes care of fetching them from Backend and parsing them into Model_RawData BB-Models
 */
var Collection_RawData = Backbone.Collection.extend({
	model: Model_RawData,
	// url: 'printerStatistics.php?subset=true',
	url: 'printerStatistics.php',
	initialize: function(){
		_.bindAll(this, "getPrintCount");
	},
	
	/**
	 * Filterfunction to retrieve exactly the print based on year, month, day, printer
	 * Unset filter needles will be ignored
	 */
	getPrintCount: function(printer, cc, y,m,d){
		var needle = {};

		if( y != null && !_.isUndefined( y ) && !isNaN( parseInt( y, 10 ) ) ){
			needle.year = y;
		}
		if( d != null && !_.isUndefined( d ) && !isNaN( parseInt( d, 10 ) ) ){
			needle.day = d;
		}
		if( m != null && !_.isUndefined( m ) && !isNaN( parseInt( m, 10 ) ) ){
			needle.month = m;
		}
		if( printer != null && !_.isUndefined( printer )){
			needle.printer = printer;
		}
		if( cc != null && !_.isUndefined( cc ) && !isNaN( parseInt( cc, 10) ) ){
			needle.costcenter = cc;
		}

		var foundElements = [];

		if(Object.keys(needle).length == 0){
			foundElements = this.models;
		}else{
			foundElements = this.where(needle);
		}

		return _.reduce( foundElements, function(memo, model){ return memo + model.get("pages"); }, 0 );

	},
});



var Model_PrinterData = Backbone.Model.extend({});

var Collection_PrinterData = Collection_RawData.extend({
	model: Model_PrinterData,

})



var Model_userData = Backbone.Model.extend({});

var Collection_userData = Backbone.Collection.extend({
	model: Model_userData,
	url: "user2cc.php",

	initialize: function(){
		_.bindAll(this, "getCCbyUser");
	},

	getCCbyUser: function(user){
		var usermodel = this.findWhere({
			"username": user
		});
		
		if( _.isUndefined(usermodel) ){
			return 0;
		}

		return parseInt(usermodel.get("costcenter"), 10);

		// this.findWhere()
	}
});


var Model_Costcenter = Backbone.Model.extend({});

var Collection_Costcenter = Backbone.Collection.extend({
	model: Model_Costcenter,
	url: 'costcenterData.php'
});

/**
 * View for Printer/Filter Menu
 * needs a set list of printer (View_Menu.printer)
 */
var View_Menu = Backbone.View.extend({
	el: "#head",

	printer: [],
	costcenter: [],

	templatePrinter: $("#tpl-filterbar").html(),

	events: {

		"click a" 						: "handleClick",
		"change select"					: "handleSelect",
		"click input[name=perCC]"		: "handlePerCC"

	},

	initialize: function(){
		_.bindAll(this, "render");
	},

	render: function(){

		// var _this = this;

		this.$el.html("");

		this.$el.append( 
			_.template( 
				this.templatePrinter, 
				{
					printers: this.printer,
					costcenter: this.costcenter,
					years: this.years.sort().reverse()
				},
				this 
			), this 
		);

		this.$el.find("select[name=year]").trigger("change");

	},

	handleSelect: function(event){
		var attribute = $(event.target).attr("name");
		var value = $(event.target).val();

		if(value == "Alle"){
			statisticsApp.statistics.filterModel.unset(attribute);
		}else{
			if(attribute != "printer"){
				value = parseInt(value, 10);
			}
			statisticsApp.statistics.filterModel.set(attribute, value);
		}

		// render Statistics with additional attribute / value combination
		

	},

	handleClick: function(event){
		console.log("click");
		event.stopPropagation();
		return false;
	},

	handlePerCC: function(event){
		statisticsApp.statistics.perCC.set("perCC", $(event.target).prop("checked"));
	}

})


var View_Statistics = Backbone.View.extend({
	el: "#body",
	filterModel: new Backbone.Model(),
	perCC: new Backbone.Model({ "perCC":true }),

	rawData: new Backbone.Collection(),

	initialize: function(){

		_.bindAll(this, "render");

		this.filterModel.on("change", this.render);
		this.perCC.on("change", this.render);

	},

	render: function(){
		
		if(this.rawData.models.length == 0){
			return false;
		}

		// Apply filterModel
		if( $.isEmptyObject( this.filterModel.attributes ) ){
			this.filteredData = this.rawData;
		}else{
			this.filteredData =  new Collection_RawData( this.rawData.where( this.filterModel.attributes ) ); 	
		}

		// nur auf Monthbase
		var uniqueYears = _.uniq( this.filteredData.pluck('year') );
		var uniqueCC = _.uniq( this.filteredData.pluck('costcenter'));

		var firstPlotline = [ "Month" ];

		if(this.perCC.get("perCC")){
			_.each(uniqueCC, function(cc){
				var costcenter = statisticsApp.costcenterData.findWhere({costcenter: String(cc)});
				if(!_.isUndefined(costcenter)){
					firstPlotline.push( costcenter.get("cc_name") );
				}else{
					firstPlotline.push(cc);
				}
					
			});	
		}else{
			firstPlotline.push("Insgesamt");
		}

		

		var plotData = [
			firstPlotline
		];
		

		_.each(uniqueYears, function(year){
			for( var month = 1; month <= 12; month++ ){

				var monthlyPrintsPerCC = [ (String(month).length == 1 ? String(0)+String(month) : String(month) ) ];

				if(this.perCC.get("perCC")){
					_.each(uniqueCC, function(cc){

						var prints = this.filteredData.getPrintCount(null, parseInt(cc, 10), year, month);
						monthlyPrintsPerCC.push(prints);

					}, this);	
				}else{
					monthlyPrintsPerCC.push( this.filteredData.getPrintCount(null, null, year, month) );
				}



				plotData.push(monthlyPrintsPerCC);

			}
		}, this);

		this.$el.html("");

		this.$el.append("<div id='chart'>");
		this.$el.append("<div id='table'>");

		var data = google.visualization.arrayToDataTable(plotData);

		var options = {
			title : 'Monthly Prints By Costcenter',
			vAxis: {title: "Prints"},
			hAxis: {
				title: "Month",
			},
			seriesType: "bars",
			// series: {5: {type: "line"}}
		};

		var chart = new google.visualization.ComboChart(this.$el.find("#chart")[0]);

		var table = new google.visualization.Table(this.$el.find("#table")[0]);

		chart.draw(data, options);
		table.draw(data);

		// var statisticsText = new View_Statistics_Text();
		// statisticsText.data = this.filteredModels;

		// this.$el.append( statisticsText.render().$el );

	}
});


// var View_Statistics_Text = Backbone.View.extend({

// 	tagName: "div",
// 	className: "statisticsText",

// 	data: new Backbone.Collection(),

// 	initialize: function(){
// 		// this.render();
// 	},

// 	render: function(){



// 		return this;
// 	}

// });


var Router = Backbone.Router.extend({
	routes: {
		"*actions": "default"
	},

	menu: null,			// View for Filterbar/Menu
	statistics: new View_Statistics(),	// current statisticsView

	rawData: new Collection_RawData(),
	printer: [],
	// costcenter: [],
	printerData: {},	// sozpr1 => collection, sozpr2 => collection
	costcenterData: {},		// costcenter1 => collection, costcenter2 => collection

	default: function(id, att){
		if(this.printer.length == 0){	// Do nothing, if not initialized
			return this;
		}
		
		if(this.menu == null){
			this.menu = new View_Menu();
			this.menu.printer = this.printer;
			this.menu.costcenter = this.costcenterData;
			this.menu.years = this.years;
			this.menu.render();	
		}

		this.statistics.rawData = this.rawData;
		this.statistics.render();
		
	},


	initialize: function(){
		_.bindAll(this, "crunchRawData");

		// Initilaize Collections
		this.costcenterData = new Collection_Costcenter();
		this.user = new Collection_userData();
		this.rawData = new Collection_RawData();

		// define function as callback for 2 step server fetch
		var fetchRawData = function(){
			this.rawData.fetch();
		}
		
		// Build two step asynchronous Data fetch
		this.listenTo(this.rawData, "sync", this.crunchRawData);	// second step fetch
		this.listenTo(this.user, "sync", fetchRawData);				// init second step fetch
		
		// initialize data fetch (first step, second step (printerJobData) via event)
		this.user.fetch();
		this.costcenterData.fetch();
		
	},

	crunchRawData: function(event){
		if(_.isUndefined( this.rawData )){
			return false;
		}

		var _this = this;

		this.costcenter = _.uniq( _this.rawData.pluck('costcenter') );
		this.printer = _.uniq( _this.rawData.pluck('printer') );
		this.years = _.uniq( _this.rawData.pluck('year') );

		this.default();

	},


})

var statisticsApp = new Router();
Backbone.history.start();