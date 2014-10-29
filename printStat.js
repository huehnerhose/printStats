
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

		attributes.costcenter = (_.isNull(attributes.costcenter) ? 0 : parseInt(attributes.costcenter));

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

	sumPrints: function(){
		return _.reduce( this.models, function(memo, model){ return memo + model.get("pages"); }, 0 );
	}

});



var Model_PrinterData = Backbone.Model.extend({});

var Collection_PrinterData = Collection_RawData.extend({
	model: Model_PrinterData,

})



var Model_userData = Backbone.Model.extend({
	url: "addCostcenter2Log.php"
});

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

	initialize: function(param){
		if(_.isUndefined(param)){
			console.log("Fuck");
		}else{
			this.rawData = param.rawData;
			this.rawData.on("sync", this.render, this);
		}

	},

	render: function(){

		this.costcenter = _.uniq( this.rawData.pluck('costcenter') );
		this.printer = _.uniq( this.rawData.pluck('printer') );
		this.years = _.uniq( this.rawData.pluck('year') );

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
		if(this.mode == "statistics"){
			if(value == "Alle"){
				statisticsApp.statistics.filterModel.unset(attribute);
			}else{
				if(attribute != "printer"){
					value = parseInt(value, 10);
				}
				statisticsApp.statistics.filterModel.set(attribute, value);
			}
		}
		// render Statistics with additional attribute / value combination


	},

	handleClick: function(event){
		// console.log("click");
		// event.stopPropagation();
		// return false;
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

	initialize: function(param){

		if(_.isUndefined(param)){
			console.log("fuck");
		}else{
			this.rawData = param.rawData;

			this.filterModel.on("change", this.render, this);
			this.perCC.on("change", this.render, this);
			this.listenTo(this.rawData, "sync", this.render, this);

		}
	},

	render: function(){

		if(this.rawData.models.length == 0){
			return false;
		}

		var plotData = this._preparePlotData();

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

		var switchedData = this._switchXY( this._preparePlotData(true) );

		var switchedOptions = {
			title : 'Monthly Prints By Costcenter',
			vAxis: {title: "Prints"},
			hAxis: {
				title: "Month",
			},
			seriesType: "bars",
			// series: {5: {type: "line"}}
		};

		switchedData = google.visualization.arrayToDataTable( switchedData );

		var table = new google.visualization.Table(this.$el.find("#table")[0]);

		chart.draw(data, options);
		table.draw(switchedData);

	},

	_switchXY: function(input){
		var output = [];

		_.each(input[0], function(oldFirstElements, index){
			if(_.isUndefined( output[index] )){
				output[index] = [];
			}

			_.each( input, function(old){
				output[index].push( old[index] );
			} );
		});

		return output;
	},

	_preparePlotData: function(renderOverYear){

		var costcenter = _.uniq( this.rawData.pluck('costcenter') );

		// Apply filterModel
		if( $.isEmptyObject( this.filterModel.attributes ) ){
			this.filteredData = this.rawData;
		}else{
			this.filteredData =  new Collection_RawData( this.rawData.where( this.filterModel.attributes ) );
		}

		var plotData = [
			this._createHeader(costcenter)
		];

		_.each( this.filteredData.groupBy("year"), function(yearData, year){
			var yearCollection = new Collection_RawData(yearData);

			var overYear = [year + "total"];

			_.each( yearCollection.groupBy("month"), function(monthData, month){

				var monthData = new Collection_RawData(monthData)

				// Mighty awesomeness
				if(_.isUndefined( Intl )){
					alert("Update your Browser");
				}else{
					var i = Intl.DateTimeFormat("en", {month: "short"});
					month = i.format( (new Date()).setMonth( month ) );
				}


				var renderDataLine = [
					month + " " + year
				];

				if(this.perCC.get("perCC")){
					// Render View per Costcenter
					_.each(costcenter, function(cc, index){

						var costcenterData = new Collection_RawData( monthData.where({costcenter: cc}) );
						renderDataLine.push( costcenterData.sumPrints() );

						overYear[index+1] = ((isNaN(overYear[index+1])) ? 0 : overYear[index+1] ) + costcenterData.sumPrints();

					}, this);
				}else{
					// render only with total prints
					renderDataLine.push( monthData.sumPrints() );
					overYear[1] = ((isNaN(overYear[1])) ? 0 : overYear[1] ) + monthData.sumPrints();
				}

				plotData.push(renderDataLine);

			}, this );
				if(renderOverYear)
					plotData.push(overYear);

		}, this );

		return plotData;

	},

	_createHeader: function(costcenterList){
		var firstPlotline = [ "Month" ];

		if(this.perCC.get("perCC")){
			_.each(costcenterList, function(cc){
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

		return firstPlotline;
	}
});


var View_Costcenter = Backbone.View.extend({

	el: "#body",
	rawData: new Collection_RawData(),
	template: $("#tpl-costcenter").html(),
	template_userRow: $("#tpl-costcenter-userRow").html(),

	userCollection: new Collection_userData(),

	events: {
		"change :input[name=costcenter]": "handleChange"
	},

	initialize: function(param){
		if(!_.isUndefined(param.rawData)){
			this.rawData = param.rawData;
		}

		this.listenTo(this.rawData, "sync", this._getData, this);

	},

	render: function(){

		if(this.userCollection.length == 0){
			this._getData();
			return this;
		}

		this.$el.html( _.template( this.template ) );

		var tbody = this.$el.find("tbody");

		var costcenterSelect = _.template($("#tpl-costcenterSelect").html());
		var groupSelect = _.template($("#tpl-groupSelect").html());

		this.userCollection.each(function(user){
							// debugger;
			tbody.append(
				_.template(
					$("#tpl-costcenterRow").html(),
					{
						username: user.get("user"),
						displayname: user.get("displayname"),
						costcenter: user.get("costcenter"),
						costcenterSelect: costcenterSelect,
						groupSelect: groupSelect,
						groups: user.get("groups"),
						prints: (_.isNull(user.get("costcenter")) ? new Collection_RawData(statisticsApp.rawData.where({user: user.get("user")})).sumPrints() : "" )
					}
				)
			)
		});

	},

	handleChange: function(event){

		// debugger;
		var userModel = this.userCollection.findWhere({user: $(event.target).prop("id")});
		userModel.set("costcenter", $(event.target).val());

		$(event.target).parents("tr").removeClass("noCostcenter");

		userModel.save();

	},

	_getData: function(){

		var usernames = _.uniq( this.rawData.pluck('user') );

		var _this = this;

		$.ajax({
			url: 'getUserInfo.php',
			type: 'POST',
			dataType: 'json',
			data: {user: usernames},
		})
		.done(function(data) {
			_this.userCollection.reset( data.users );
			_this.render();
		});



	}

});


var Router = Backbone.Router.extend({
	routes: {
		"costcenter": "costcenter",
		"*actions": "default"
	},

	menu: undefined,			// View for Filterbar/Menu
	statistics: undefined,	// current statisticsView
	view_costcenter: undefined,

	currentView: undefined,

	rawData: new Collection_RawData(),
	costcenterData: null,
	user: null,

	default: function(id, att){
		this.menu.mode = "statistics";

		if(_.isUndefined( this.statistics )){
			this.statistics = new View_Statistics({
				rawData: this.rawData
			});
		}

		if(!_.isUndefined( this.currentView ) || this.currentView == "statistics"){
			this.statistics.render();
		}

	},

	costcenter: function(){
		// Render costcenter view in #body
		this.menu.mode = "costcenter";

		if(_.isUndefined( this.view_costcenter )){
			this.view_costcenter = new View_Costcenter({
				rawData: this.rawData
			});
		}

		// if(!_.isUndefined( this.currentView ) || this.currentView == "costcenter"){
			this.view_costcenter.render();
		// }

		this.currentView = "costcenter";

	},

	initialize: function(){

		// Initilaize Collections
		this.costcenterData = new Collection_Costcenter();
		this.user = new Collection_userData();
		this.rawData = new Collection_RawData();

		// define function as callback for 2 step server fetch
		var fetchRawData = function(){
			this.rawData.fetch();
		}

		// Build two step asynchronous Data fetch
		this.listenTo(this.user, "sync", fetchRawData);				// init second step fetch

		// initialize data fetch (first step, second step (printerJobData) via event)
		this.user.fetch();
		this.costcenterData.fetch();

		this.menu = new View_Menu({
			rawData: this.rawData
		});

	},


})

var statisticsApp = new Router();
Backbone.history.start();