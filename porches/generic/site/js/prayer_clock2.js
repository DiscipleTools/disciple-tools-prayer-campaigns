(function (cjs, an) {

var p; // shortcut to reference prototypes
var lib={};var ss={};var img={};
lib.ssMetadata = [];


// symbols:
// helper functions:

function mc_symbol_clone() {
	var clone = this._cloneProps(new this.constructor(this.mode, this.startPosition, this.loop));
	clone.gotoAndStop(this.currentFrame);
	clone.paused = this.paused;
	clone.framerate = this.framerate;
	return clone;
}

function getMCSymbolPrototype(symbol, nominalBounds, frameBounds) {
	var prototype = cjs.extend(symbol, cjs.MovieClip);
	prototype.clone = mc_symbol_clone;
	prototype.nominalBounds = nominalBounds;
	prototype.frameBounds = frameBounds;
	return prototype;
	}


(lib.start_btn = function(mode,startPosition,loop) {
	this.initialize(mode,startPosition,loop,{});

	// text
	this.btnLabel = new cjs.Text("", "bold 50px 'Arial'", "#FFFFFF");
	this.btnLabel.name = "btnLabel";
	this.btnLabel.textAlign = "center";
	this.btnLabel.lineHeight = 56;
	this.btnLabel.lineWidth = 288;
	this.btnLabel.parent = this;
	this.btnLabel.setTransform(0,-15.7,0.563,0.563);

	this.timeline.addTween(cjs.Tween.get(this.btnLabel).wait(1).to({x:0.5},0).wait(3));

	// Layer_1
	this.shape = new cjs.Shape();
	this.shape.graphics.f("#16A1FD").s().p("AuDEsIAApXIcHAAIAAJXg");

	this.shape_1 = new cjs.Shape();
	this.shape_1.graphics.f("#FEB30B").s().p("AuDEsIAApXIcHAAIAAJXg");
	this.shape_1.setTransform(0.5,0);

	this.timeline.addTween(cjs.Tween.get({}).to({state:[{t:this.shape,p:{x:0}}]}).to({state:[{t:this.shape_1}]},1).to({state:[{t:this.shape,p:{x:0.5}}]},1).to({state:[{t:this.shape,p:{x:0.5}}]},1).wait(1));

}).prototype = p = new cjs.MovieClip();
p.nominalBounds = new cjs.Rectangle(-90,-30,180,60);


(lib.SecondHand = function(mode,startPosition,loop) {
	this.initialize(mode,startPosition,loop,{});

	// timeline functions:
	this.frame_0 = function() {
		/* 
		
		*/
	}

	// actions tween:
	this.timeline.addTween(cjs.Tween.get(this).call(this.frame_0).wait(1));

	// Layer_1
	this.shape = new cjs.Shape();
	this.shape.graphics.f("#FFFFFF").s().p("AhjbWMAAAg2rIDHEiIgoTsIg8edg");
	this.shape.setTransform(10,-155);

	this.shape_1 = new cjs.Shape();
	this.shape_1.graphics.f("#CCCCCC").s().p("AAAbWIg8+dIgnzsIDHkiMAAAA2rg");
	this.shape_1.setTransform(-10,-155);

	this.timeline.addTween(cjs.Tween.get({}).to({state:[{t:this.shape_1},{t:this.shape}]}).wait(1));

}).prototype = getMCSymbolPrototype(lib.SecondHand, new cjs.Rectangle(-20,-330,40,350), null);


(lib.Group_4 = function(mode,startPosition,loop) {
	this.initialize(mode,startPosition,loop,{});

	// Layer_1
	this.shape = new cjs.Shape();
	this.shape.graphics.f("#73C7FE").s().p("AhGBLIADhTIA7hVIBPBZIgNBig");
	this.shape.setTransform(35.5,31.9);

	this.shape_1 = new cjs.Shape();
	this.shape_1.graphics.f("#73C7FE").s().p("Ag9AFIAMgSIBZgaIAWBHIgSAIg");
	this.shape_1.setTransform(7.4,67.1);

	this.shape_2 = new cjs.Shape();
	this.shape_2.graphics.f("#73C7FE").s().p("AgGAKIAGgUIAHAFIgIAQg");
	this.shape_2.setTransform(0.7,66.3);

	this.shape_3 = new cjs.Shape();
	this.shape_3.graphics.f("#73C7FE").s().p("AgygCIABgPIBMgMIAYA7g");
	this.shape_3.setTransform(19.4,72.3);

	this.shape_4 = new cjs.Shape();
	this.shape_4.graphics.f("#73C7FE").s().p("AhLgtIBfgeIA3B7IgeARIhTALg");
	this.shape_4.setTransform(17.9,62.2);

	this.shape_5 = new cjs.Shape();
	this.shape_5.graphics.f("#73C7FE").s().p("AgxBEQAahIAkhCIAGgCIAfBzIhdAdg");
	this.shape_5.setTransform(6,57.8);

	this.shape_6 = new cjs.Shape();
	this.shape_6.graphics.f("#73C7FE").s().p("AhOgNIATggIAygWIBYAzIgrA1IhfAfg");
	this.shape_6.setTransform(16.3,50.1);

	this.shape_7 = new cjs.Shape();
	this.shape_7.graphics.f("#73C7FE").s().p("AAIgTIAKAXIgkAQIAagng");
	this.shape_7.setTransform(13,42.4);

	this.shape_8 = new cjs.Shape();
	this.shape_8.graphics.f("#73C7FE").s().p("Ag3A1IgNgdQA0hEA8g6IAZAlIgCBWIgiBSg");
	this.shape_8.setTransform(21.1,37.4);

	this.shape_9 = new cjs.Shape();
	this.shape_9.graphics.f("#73C7FE").s().p("AhHBMIAOhdICBg7IgRCZg");
	this.shape_9.setTransform(49.2,33.6);

	this.shape_10 = new cjs.Shape();
	this.shape_10.graphics.f("#73C7FE").s().p("AhpgGIAmg4IAfgUIB1AMIAYBeIiFA7g");
	this.shape_10.setTransform(45.7,22.7);

	this.shape_11 = new cjs.Shape();
	this.shape_11.graphics.f("#73C7FE").s().p("Ag4AcQA4g0A6goIhZCBg");
	this.shape_11.setTransform(31.7,23.9);

	this.shape_12 = new cjs.Shape();
	this.shape_12.graphics.f("#73C7FE").s().p("Ag8gOQAxgVAzgQIAVAQIgCA4Ih0Afg");
	this.shape_12.setTransform(60.3,9.6);

	this.shape_13 = new cjs.Shape();
	this.shape_13.graphics.f("#73C7FE").s().p("Ag2AWQA1geA0gYIAEBBg");
	this.shape_13.setTransform(48.3,11.7);

	this.shape_14 = new cjs.Shape();
	this.shape_14.graphics.f("#73C7FE").s().p("AgOAAIAdgIIAAARg");
	this.shape_14.setTransform(91.1,96.5);

	this.shape_15 = new cjs.Shape();
	this.shape_15.graphics.f("#73C7FE").s().p("AguAWIgZg8IAcgRIBzBjIgPAMg");
	this.shape_15.setTransform(30.1,73.3);

	this.shape_16 = new cjs.Shape();
	this.shape_16.graphics.f("#73C7FE").s().p("AhfAAIBIheIB3BVIhVBog");
	this.shape_16.setTransform(36.3,67.1);

	this.shape_17 = new cjs.Shape();
	this.shape_17.graphics.f("#73C7FE").s().p("AhGAPIAphMIBkASIhKBpg");
	this.shape_17.setTransform(41.5,55.5);

	this.shape_18 = new cjs.Shape();
	this.shape_18.graphics.f("#73C7FE").s().p("AhGggIArg4IBiA8IhWB1g");
	this.shape_18.setTransform(27.7,57.8);

	this.shape_19 = new cjs.Shape();
	this.shape_19.graphics.f("#73C7FE").s().p("AhPAIIAhhOIB+AUIg7B5g");
	this.shape_19.setTransform(33.1,47.1);

	this.shape_20 = new cjs.Shape();
	this.shape_20.graphics.f("#73C7FE").s().p("AgXAAIAvgLIghAXg");
	this.shape_20.setTransform(67.7,4);

	this.shape_21 = new cjs.Shape();
	this.shape_21.graphics.f("#73C7FE").s().p("AhagEIAxgjQAngJAmgFIA4AoIgwA9IiHAGg");
	this.shape_21.setTransform(76.3,6.3);

	this.shape_22 = new cjs.Shape();
	this.shape_22.graphics.f("#73C7FE").s().p("AgsgNQAsgGAsgBIglApg");
	this.shape_22.setTransform(85.2,2.2);

	this.shape_23 = new cjs.Shape();
	this.shape_23.graphics.f("#73C7FE").s().p("AgHgHIAPAAIAAAPg");
	this.shape_23.setTransform(91.9,0.8);

	this.shape_24 = new cjs.Shape();
	this.shape_24.graphics.f("#73C7FE").s().p("AATBZIhMhQIBchrIAXAVIAACmIgJALg");
	this.shape_24.setTransform(86.9,10.7);

	this.shape_25 = new cjs.Shape();
	this.shape_25.graphics.f("#73C7FE").s().p("AhBAlIgkhsICAAAIBLBJIg5BHg");
	this.shape_25.setTransform(77.7,19.6);

	this.shape_26 = new cjs.Shape();
	this.shape_26.graphics.f("#73C7FE").s().p("Ag2A9IgXhdIB3gfIAkBjIgLAcg");
	this.shape_26.setTransform(62.3,18.8);

	this.shape_27 = new cjs.Shape();
	this.shape_27.graphics.f("#73C7FE").s().p("AhEATIAXhCIBxAjIgxA8g");
	this.shape_27.setTransform(75,28.8);

	this.shape_28 = new cjs.Shape();
	this.shape_28.graphics.f("#73C7FE").s().p("AgyhMIB1AEIgrB4IhbAdg");
	this.shape_28.setTransform(62.2,33.4);

	this.shape_29 = new cjs.Shape();
	this.shape_29.graphics.f("#73C7FE").s().p("Ag2gNIAZg/IBUAZIg+CAg");
	this.shape_29.setTransform(71,39.7);

	this.shape_30 = new cjs.Shape();
	this.shape_30.graphics.f("#73C7FE").s().p("AhKAuIBriGIAqAMIAABxIg2A0g");
	this.shape_30.setTransform(85.1,29.4);

	this.shape_31 = new cjs.Shape();
	this.shape_31.graphics.f("#73C7FE").s().p("AAYgVIAAArIgvABg");
	this.shape_31.setTransform(90.3,36.2);

	this.shape_32 = new cjs.Shape();
	this.shape_32.graphics.f("#73C7FE").s().p("AgaAoIAEhfIAxgBIAABxg");
	this.shape_32.setTransform(90,44.9);

	this.shape_33 = new cjs.Shape();
	this.shape_33.graphics.f("#73C7FE").s().p("AhiApIBRhvIB0BJIgwBDIhlAAg");
	this.shape_33.setTransform(51.5,58.3);

	this.shape_34 = new cjs.Shape();
	this.shape_34.graphics.f("#73C7FE").s().p("AhNAZIAhhBIB6ABIg0BQg");
	this.shape_34.setTransform(46.7,46);

	this.shape_35 = new cjs.Shape();
	this.shape_35.graphics.f("#73C7FE").s().p("Ag3AEIA1hPIA6CWg");
	this.shape_35.setTransform(55.8,50.2);

	this.shape_36 = new cjs.Shape();
	this.shape_36.graphics.f("#73C7FE").s().p("AhBg9IBUgcIAvBWIhJBdg");
	this.shape_36.setTransform(63,48.4);

	this.shape_37 = new cjs.Shape();
	this.shape_37.graphics.f("#73C7FE").s().p("AhSAnIBCiEIBjApIgHBlIgyAug");
	this.shape_37.setTransform(78.9,44.3);

	this.shape_38 = new cjs.Shape();
	this.shape_38.graphics.f("#73C7FE").s().p("AhZASIBJhcIBqA4IhGBdg");
	this.shape_38.setTransform(71.9,56.1);

	this.shape_39 = new cjs.Shape();
	this.shape_39.graphics.f("#73C7FE").s().p("AgvBLIgehvIAvg/IBsA2IhUCQg");
	this.shape_39.setTransform(65.5,69.1);

	this.shape_40 = new cjs.Shape();
	this.shape_40.graphics.f("#73C7FE").s().p("AhqAnIABAAIBWhqIBjAAIAbBmIh1Ahg");
	this.shape_40.setTransform(48.9,73.4);

	this.shape_41 = new cjs.Shape();
	this.shape_41.graphics.f("#73C7FE").s().p("AgzgMIAJgHIBZAXIAFAQg");
	this.shape_41.setTransform(42.3,80.7);

	this.shape_42 = new cjs.Shape();
	this.shape_42.graphics.f("#73C7FE").s().p("AhdABIgFgQIB6gjIBLAuIgmA3g");
	this.shape_42.setTransform(57.8,82.9);

	this.shape_43 = new cjs.Shape();
	this.shape_43.graphics.f("#73C7FE").s().p("AhIAHIAng6IBqA7IgHAsg");
	this.shape_43.setTransform(72.1,87.7);

	this.shape_44 = new cjs.Shape();
	this.shape_44.graphics.f("#73C7FE").s().p("AhhALIA4hjICLBSIg1Bfg");
	this.shape_44.setTransform(75.3,78.8);

	this.shape_45 = new cjs.Shape();
	this.shape_45.graphics.f("#73C7FE").s().p("Ag0gtIAygwIA3ATIAACLIgWAdg");
	this.shape_45.setTransform(87.3,59.1);

	this.shape_46 = new cjs.Shape();
	this.shape_46.graphics.f("#73C7FE").s().p("AhcAeIBiiNIBXCTIgvBMg");
	this.shape_46.setTransform(80.8,65.9);

	this.shape_47 = new cjs.Shape();
	this.shape_47.graphics.f("#73C7FE").s().p("AhAAQIAIguIB4gMIABABIAABKIgtAKg");
	this.shape_47.setTransform(86.1,91.6);

	this.shape_48 = new cjs.Shape();
	this.shape_48.graphics.f("#73C7FE").s().p("AAohXIAUALIAACWIh3AOg");
	this.shape_48.setTransform(86.6,78.9);

	this.shape_49 = new cjs.Shape();
	this.shape_49.graphics.f("#73C7FE").s().p("AgHAGIAPgUIAAAcg");
	this.shape_49.setTransform(91.8,68.6);

	this.timeline.addTween(cjs.Tween.get({}).to({state:[{t:this.shape_49},{t:this.shape_48},{t:this.shape_47},{t:this.shape_46},{t:this.shape_45},{t:this.shape_44},{t:this.shape_43},{t:this.shape_42},{t:this.shape_41},{t:this.shape_40},{t:this.shape_39},{t:this.shape_38},{t:this.shape_37},{t:this.shape_36},{t:this.shape_35},{t:this.shape_34},{t:this.shape_33},{t:this.shape_32},{t:this.shape_31},{t:this.shape_30},{t:this.shape_29},{t:this.shape_28},{t:this.shape_27},{t:this.shape_26},{t:this.shape_25},{t:this.shape_24},{t:this.shape_23},{t:this.shape_22},{t:this.shape_21},{t:this.shape_20},{t:this.shape_19},{t:this.shape_18},{t:this.shape_17},{t:this.shape_16},{t:this.shape_15},{t:this.shape_14},{t:this.shape_13},{t:this.shape_12},{t:this.shape_11},{t:this.shape_10},{t:this.shape_9},{t:this.shape_8},{t:this.shape_7},{t:this.shape_6},{t:this.shape_5},{t:this.shape_4},{t:this.shape_3},{t:this.shape_2},{t:this.shape_1},{t:this.shape}]}).wait(1));

}).prototype = getMCSymbolPrototype(lib.Group_4, new cjs.Rectangle(0,0,92.7,97.4), null);


(lib.Group_3 = function(mode,startPosition,loop) {
	this.initialize(mode,startPosition,loop,{});

	// Layer_1
	this.shape = new cjs.Shape();
	this.shape.graphics.f("#45B4FD").s().p("AhOgDIAIgkIBZgjIA8CJIhoAMQgXgfgegvg");
	this.shape.setTransform(21.6,80.3);

	this.shape_1 = new cjs.Shape();
	this.shape_1.graphics.f("#45B4FD").s().p("Ag6hKIB1AnIhcBug");
	this.shape_1.setTransform(91.4,33.7);

	this.shape_2 = new cjs.Shape();
	this.shape_2.graphics.f("#45B4FD").s().p("AhRgLICFhEIABABIAdCdIibABg");
	this.shape_2.setTransform(79.2,33.9);

	this.shape_3 = new cjs.Shape();
	this.shape_3.graphics.f("#45B4FD").s().p("AhagEIAdg3ICXgCIgyBXIgbAkg");
	this.shape_3.setTransform(78,48.7);

	this.shape_4 = new cjs.Shape();
	this.shape_4.graphics.f("#45B4FD").s().p("AgaAiIgDg+IA8gFIgyBDg");
	this.shape_4.setTransform(51,93);

	this.shape_5 = new cjs.Shape();
	this.shape_5.graphics.f("#45B4FD").s().p("AgsAVIgChEIA3gGIAiAnIAEBAIg4AEg");
	this.shape_5.setTransform(43.1,91.5);

	this.shape_6 = new cjs.Shape();
	this.shape_6.graphics.f("#45B4FD").s().p("AgLgtIBIBNIh5AOg");
	this.shape_6.setTransform(37.2,82.1);

	this.shape_7 = new cjs.Shape();
	this.shape_7.graphics.f("#45B4FD").s().p("Ag3hCIAfgIIBQA1Ig0Bgg");
	this.shape_7.setTransform(29.9,79.1);

	this.shape_8 = new cjs.Shape();
	this.shape_8.graphics.f("#45B4FD").s().p("AhGgHIAmhIIBnBEIhHBbg");
	this.shape_8.setTransform(71.7,56.7);

	this.shape_9 = new cjs.Shape();
	this.shape_9.graphics.f("#45B4FD").s().p("Aggg4IBDAAIAEAIIhNBpg");
	this.shape_9.setTransform(62.2,77.9);

	this.shape_10 = new cjs.Shape();
	this.shape_10.graphics.f("#45B4FD").s().p("AhDA/IBVilIAyAjIgEB7IggAqIhCAFg");
	this.shape_10.setTransform(51.3,79.1);

	this.shape_11 = new cjs.Shape();
	this.shape_11.graphics.f("#45B4FD").s().p("AhRAWIBSiAIBRAzIhWCig");
	this.shape_11.setTransform(44.2,74.2);

	this.shape_12 = new cjs.Shape();
	this.shape_12.graphics.f("#45B4FD").s().p("AhqAPIAdhEIBzgVIBGBYIg6A8IhGACg");
	this.shape_12.setTransform(60.4,64.1);

	this.shape_13 = new cjs.Shape();
	this.shape_13.graphics.f("#45B4FD").s().p("AhdAUIBThvIBoA4IhSB/g");
	this.shape_13.setTransform(34.2,66.6);

	this.shape_14 = new cjs.Shape();
	this.shape_14.graphics.f("#45B4FD").s().p("AhbgUICsgrIALA1IgfBJg");
	this.shape_14.setTransform(42.9,58.9);

	this.shape_15 = new cjs.Shape();
	this.shape_15.graphics.f("#45B4FD").s().p("AhTglIA/ghIBoA0IgmBGIhsATg");
	this.shape_15.setTransform(59.2,50.7);

	this.shape_16 = new cjs.Shape();
	this.shape_16.graphics.f("#45B4FD").s().p("AgkhPIBxBEIAGAvIilAsg");
	this.shape_16.setTransform(42.3,47.9);

	this.shape_17 = new cjs.Shape();
	this.shape_17.graphics.f("#45B4FD").s().p("AhMAaIBJhoIAuAUIAiBqIg7Afg");
	this.shape_17.setTransform(48.9,38);

	this.shape_18 = new cjs.Shape();
	this.shape_18.graphics.f("#45B4FD").s().p("AgxA8IglhpICChCIAiBCIAJBiIggA7g");
	this.shape_18.setTransform(62.6,36.9);

	this.shape_19 = new cjs.Shape();
	this.shape_19.graphics.f("#45B4FD").s().p("AhJAbIAdhcIB2AeIhFBlg");
	this.shape_19.setTransform(40.2,33.5);

	this.shape_20 = new cjs.Shape();
	this.shape_20.graphics.f("#45B4FD").s().p("AhJAkIAvhQIBkgWIgaCGg");
	this.shape_20.setTransform(43.3,22.6);

	this.shape_21 = new cjs.Shape();
	this.shape_21.graphics.f("#45B4FD").s().p("AhZA9IAbiMIB8AnIAcA2IiCBCg");
	this.shape_21.setTransform(57.7,23.5);

	this.shape_22 = new cjs.Shape();
	this.shape_22.graphics.f("#45B4FD").s().p("Ahcg9IC5A8Ih6BAg");
	this.shape_22.setTransform(74,25.8);

	this.shape_23 = new cjs.Shape();
	this.shape_23.graphics.f("#45B4FD").s().p("AhggoIAJgBIC4A7IhoAYg");
	this.shape_23.setTransform(41.1,13.2);

	this.shape_24 = new cjs.Shape();
	this.shape_24.graphics.f("#45B4FD").s().p("AgbgiIA3gCIg1BJg");
	this.shape_24.setTransform(45.6,101.1);

	this.shape_25 = new cjs.Shape();
	this.shape_25.graphics.f("#45B4FD").s().p("AhEgdIgCgmIBlgFIAmAkIACBRIgUAcQg/gug4g4g");
	this.shape_25.setTransform(35,101.5);

	this.shape_26 = new cjs.Shape();
	this.shape_26.graphics.f("#45B4FD").s().p("AhAgXIB9gMIAEBAIhjAHg");
	this.shape_26.setTransform(31.5,90.4);

	this.shape_27 = new cjs.Shape();
	this.shape_27.graphics.f("#45B4FD").s().p("AglgcIAvgEIAcA9IgZAEQgdghgVgcg");
	this.shape_27.setTransform(23.4,91.6);

	this.shape_28 = new cjs.Shape();
	this.shape_28.graphics.f("#45B4FD").s().p("AgIgHIAQgEIABAXg");
	this.shape_28.setTransform(26.3,96.5);

	this.shape_29 = new cjs.Shape();
	this.shape_29.graphics.f("#45B4FD").s().p("AgQgaIAhAbIgFAaQgOgZgOgcg");
	this.shape_29.setTransform(12.1,76.4);

	this.shape_30 = new cjs.Shape();
	this.shape_30.graphics.f("#45B4FD").s().p("AhGAsIAAAAQgOgdgQgrIAxg3ICYB6Ih4Atg");
	this.shape_30.setTransform(16.4,67.1);

	this.shape_31 = new cjs.Shape();
	this.shape_31.graphics.f("#45B4FD").s().p("AhogQIAXgeIA6gmICAA1IhWB0g");
	this.shape_31.setTransform(22.3,59.6);

	this.shape_32 = new cjs.Shape();
	this.shape_32.graphics.f("#45B4FD").s().p("Ag1g9IBrArIhGBQQgWg6gPhBg");
	this.shape_32.setTransform(7.6,57.1);

	this.shape_33 = new cjs.Shape();
	this.shape_33.graphics.f("#45B4FD").s().p("AguAZIgBgCQgJgugFgtIBUAjIAnBmg");
	this.shape_33.setTransform(6.8,47.5);

	this.shape_34 = new cjs.Shape();
	this.shape_34.graphics.f("#45B4FD").s().p("Ag8gQIAhhCIBYAkIgZBdIg5Akg");
	this.shape_34.setTransform(15.8,45.6);

	this.shape_35 = new cjs.Shape();
	this.shape_35.graphics.f("#45B4FD").s().p("AhXApIAbhdIBfgpIA1AaIgwCig");
	this.shape_35.setTransform(28.9,46.3);

	this.shape_36 = new cjs.Shape();
	this.shape_36.graphics.f("#45B4FD").s().p("AhqA8IBPiaICGA2IgcBeIhdApg");
	this.shape_36.setTransform(24.2,30.8);

	this.shape_37 = new cjs.Shape();
	this.shape_37.graphics.f("#45B4FD").s().p("Ag6AsIgEgyIAbhLIBiBfIgiBDg");
	this.shape_37.setTransform(6.4,35.3);

	this.shape_38 = new cjs.Shape();
	this.shape_38.graphics.f("#45B4FD").s().p("AgKgbIAVAAIgVA3g");
	this.shape_38.setTransform(1.1,29.6);

	this.shape_39 = new cjs.Shape();
	this.shape_39.graphics.f("#45B4FD").s().p("AhKATIgXhqIAEgXIBwAAIBPBJIhKCUg");
	this.shape_39.setTransform(10.8,24.6);

	this.shape_40 = new cjs.Shape();
	this.shape_40.graphics.f("#45B4FD").s().p("AgLAmQABgkAFgoIARBNg");
	this.shape_40.setTransform(1.3,22.3);

	this.shape_41 = new cjs.Shape();
	this.shape_41.graphics.f("#45B4FD").s().p("AhYAPIB1hRIA8A0IgvBRg");
	this.shape_41.setTransform(30.9,19.4);

	this.shape_42 = new cjs.Shape();
	this.shape_42.graphics.f("#45B4FD").s().p("AhgAFIBKhPIBQAbIAnAoIh5BSg");
	this.shape_42.setTransform(23.5,12.9);

	this.shape_43 = new cjs.Shape();
	this.shape_43.graphics.f("#45B4FD").s().p("AhEgXIA/ggIAPAFIBNAuIg6A7IhyABQAFgmAMgpg");
	this.shape_43.setTransform(10.2,7.2);

	this.shape_44 = new cjs.Shape();
	this.shape_44.graphics.f("#45B4FD").s().p("AgLgRIAiAMIgtAXQAFgSAGgRg");
	this.shape_44.setTransform(5.9,1.8);

	this.shape_45 = new cjs.Shape();
	this.shape_45.graphics.f("#45B4FD").s().p("AgXgMIAvAPIgJAKg");
	this.shape_45.setTransform(17.8,4.7);

	this.timeline.addTween(cjs.Tween.get({}).to({state:[{t:this.shape_45},{t:this.shape_44},{t:this.shape_43},{t:this.shape_42},{t:this.shape_41},{t:this.shape_40},{t:this.shape_39},{t:this.shape_38},{t:this.shape_37},{t:this.shape_36},{t:this.shape_35},{t:this.shape_34},{t:this.shape_33},{t:this.shape_32},{t:this.shape_31},{t:this.shape_30},{t:this.shape_29},{t:this.shape_28},{t:this.shape_27},{t:this.shape_26},{t:this.shape_25},{t:this.shape_24},{t:this.shape_23},{t:this.shape_22},{t:this.shape_21},{t:this.shape_20},{t:this.shape_19},{t:this.shape_18},{t:this.shape_17},{t:this.shape_16},{t:this.shape_15},{t:this.shape_14},{t:this.shape_13},{t:this.shape_12},{t:this.shape_11},{t:this.shape_10},{t:this.shape_9},{t:this.shape_8},{t:this.shape_7},{t:this.shape_6},{t:this.shape_5},{t:this.shape_4},{t:this.shape_3},{t:this.shape_2},{t:this.shape_1},{t:this.shape}]}).wait(1));

}).prototype = getMCSymbolPrototype(lib.Group_3, new cjs.Rectangle(0.1,0,97.3,108.9), null);


(lib.Group_2 = function(mode,startPosition,loop) {
	this.initialize(mode,startPosition,loop,{});

	// Layer_1
	this.shape = new cjs.Shape();
	this.shape.graphics.f("#16A1FD").s().p("AhKAfIA8hmIBcA1QhJAzhSAmg");
	this.shape.setTransform(106.2,81.1);

	this.shape_1 = new cjs.Shape();
	this.shape_1.graphics.f("#16A1FD").s().p("AhOAoIgShKIBzgnIBOArIg8Bog");
	this.shape_1.setTransform(94.3,76.6);

	this.shape_2 = new cjs.Shape();
	this.shape_2.graphics.f("#16A1FD").s().p("AhugFIBrg2IBxAhIABAmQgcANgdALIgBAAIhvAYg");
	this.shape_2.setTransform(86.7,87.3);

	this.shape_3 = new cjs.Shape();
	this.shape_3.graphics.f("#16A1FD").s().p("AATg0IA/BQQhSAUhQAGg");
	this.shape_3.setTransform(72.7,91.7);

	this.shape_4 = new cjs.Shape();
	this.shape_4.graphics.f("#16A1FD").s().p("Ag0A0IgogtIBhg7IBYAzIgvA0QgcACgjAAg");
	this.shape_4.setTransform(59,92);

	this.shape_5 = new cjs.Shape();
	this.shape_5.graphics.f("#16A1FD").s().p("AhOAUIAAgkIBTgYIBKBQQhSgEhLgQg");
	this.shape_5.setTransform(44.9,93.1);

	this.shape_6 = new cjs.Shape();
	this.shape_6.graphics.f("#16A1FD").s().p("Ag3AeIgYhBICMggIATBMIhkA7g");
	this.shape_6.setTransform(51.2,85.1);

	this.shape_7 = new cjs.Shape();
	this.shape_7.graphics.f("#16A1FD").s().p("Ag+AWIgPhPIBDgOIBYBbIgyA0g");
	this.shape_7.setTransform(66.1,83.9);

	this.shape_8 = new cjs.Shape();
	this.shape_8.graphics.f("#16A1FD").s().p("AgyAJIgFgcIBVgYIAaBBIhNAWg");
	this.shape_8.setTransform(39.1,86);

	this.shape_9 = new cjs.Shape();
	this.shape_9.graphics.f("#16A1FD").s().p("AhAAAIgBgZIBpgMIAaAhIAAAqQhHgRg7gVg");
	this.shape_9.setTransform(29.7,91.3);

	this.shape_10 = new cjs.Shape();
	this.shape_10.graphics.f("#16A1FD").s().p("AgbgEIA2gOIABAlQgdgMgagLg");
	this.shape_10.setTransform(19.6,89);

	this.shape_11 = new cjs.Shape();
	this.shape_11.graphics.f("#16A1FD").s().p("AgkAuIBJhkIgsBtg");
	this.shape_11.setTransform(28.3,40.4);

	this.shape_12 = new cjs.Shape();
	this.shape_12.graphics.f("#16A1FD").s().p("AgagNIAFgHIAwAbIgCAOQgXgPgcgTg");
	this.shape_12.setTransform(2.7,80.2);

	this.shape_13 = new cjs.Shape();
	this.shape_13.graphics.f("#16A1FD").s().p("AgxAgIBBhZIAjAUIg0Bfg");
	this.shape_13.setTransform(6,74.3);

	this.shape_14 = new cjs.Shape();
	this.shape_14.graphics.f("#16A1FD").s().p("Ag+AyIACgBIARgYIAAAAIBIhkIAiAMIhtCKg");
	this.shape_14.setTransform(21.1,53.3);

	this.shape_15 = new cjs.Shape();
	this.shape_15.graphics.f("#16A1FD").s().p("AgmAhIA9hUIAQAcIgtBLg");
	this.shape_15.setTransform(12.1,64.3);

	this.shape_16 = new cjs.Shape();
	this.shape_16.graphics.f("#16A1FD").s().p("AhEghIB2gPIATBXIhfAJg");
	this.shape_16.setTransform(26.2,82.9);

	this.shape_17 = new cjs.Shape();
	this.shape_17.graphics.f("#16A1FD").s().p("AhPgJIADgTIB5gMIAkBCIhCAPQgsgUgygeg");
	this.shape_17.setTransform(14.1,83.8);

	this.shape_18 = new cjs.Shape();
	this.shape_18.graphics.f("#16A1FD").s().p("AgVhXICCBDIANBPIjzAdg");
	this.shape_18.setTransform(18.8,71.3);

	this.shape_19 = new cjs.Shape();
	this.shape_19.graphics.f("#16A1FD").s().p("AgzgrIAqg0IA9CnIhOAYg");
	this.shape_19.setTransform(35.8,73.8);

	this.shape_20 = new cjs.Shape();
	this.shape_20.graphics.f("#16A1FD").s().p("AhhgUIBbg3IBZAZIAPBMIhmAyg");
	this.shape_20.setTransform(75.6,78.3);

	this.shape_21 = new cjs.Shape();
	this.shape_21.graphics.f("#16A1FD").s().p("AhJhJIAegIIB1BrIhcA4g");
	this.shape_21.setTransform(66.7,67.7);

	this.shape_22 = new cjs.Shape();
	this.shape_22.graphics.f("#16A1FD").s().p("AgVhQIBJgUIA4CaIjXAvg");
	this.shape_22.setTransform(53.3,70.7);

	this.shape_23 = new cjs.Shape();
	this.shape_23.graphics.f("#16A1FD").s().p("AhIgpIAqg8IBKgXIAdBHIhVCzg");
	this.shape_23.setTransform(42.8,67.7);

	this.shape_24 = new cjs.Shape();
	this.shape_24.graphics.f("#16A1FD").s().p("AhrAiIBsiHIBrBfIhSBsg");
	this.shape_24.setTransform(27.8,58.3);

	this.shape_25 = new cjs.Shape();
	this.shape_25.graphics.f("#16A1FD").s().p("AhbgbQACABBMgTIBLgSIAeBqIhMAVg");
	this.shape_25.setTransform(37.7,50.2);

	this.shape_26 = new cjs.Shape();
	this.shape_26.graphics.f("#16A1FD").s().p("AgMg9IAAgBIAQgYIBGBcIABAsIiWAkg");
	this.shape_26.setTransform(35.9,37.5);

	this.shape_27 = new cjs.Shape();
	this.shape_27.graphics.f("#16A1FD").s().p("AhRgoIBHgfIBdA3IgmAwIhsAog");
	this.shape_27.setTransform(52.5,41.8);

	this.shape_28 = new cjs.Shape();
	this.shape_28.graphics.f("#16A1FD").s().p("AhXgnIBhgiIBOBZIgTAfIhxAbg");
	this.shape_28.setTransform(55.3,54.1);

	this.shape_29 = new cjs.Shape();
	this.shape_29.graphics.f("#16A1FD").s().p("AhfgCIBLhiIB0BXIhIByg");
	this.shape_29.setTransform(72.6,59.4);

	this.shape_30 = new cjs.Shape();
	this.shape_30.graphics.f("#16A1FD").s().p("AhjAuIBNhwIBigFIAXBpIhwAmg");
	this.shape_30.setTransform(85.7,65.3);

	this.shape_31 = new cjs.Shape();
	this.shape_31.graphics.f("#16A1FD").s().p("Ag5gFIAeg2IBVB3g");
	this.shape_31.setTransform(107.1,71.8);

	this.shape_32 = new cjs.Shape();
	this.shape_32.graphics.f("#16A1FD").s().p("AhFgIIAyhFIAtAMIAsBvIhFAgg");
	this.shape_32.setTransform(43.7,29.2);

	this.shape_33 = new cjs.Shape();
	this.shape_33.graphics.f("#16A1FD").s().p("AhUA6IBZh8IAzARIAdAzIh/BBg");
	this.shape_33.setTransform(50.8,14.8);

	this.shape_34 = new cjs.Shape();
	this.shape_34.graphics.f("#16A1FD").s().p("AhDgTIAngzIBfA6Ig6BTg");
	this.shape_34.setTransform(64.2,48.1);

	this.shape_35 = new cjs.Shape();
	this.shape_35.graphics.f("#16A1FD").s().p("AhRAfIA2hdIBIAiIAkAxIgWAmIheAEg");
	this.shape_35.setTransform(87.1,51.1);

	this.shape_36 = new cjs.Shape();
	this.shape_36.graphics.f("#16A1FD").s().p("AgYA7IgWhuIARgfIBMBqIgfA7g");
	this.shape_36.setTransform(99,62.6);

	this.shape_37 = new cjs.Shape();
	this.shape_37.graphics.f("#16A1FD").s().p("Ag9AQIArhLIBQAiIg3BVg");
	this.shape_37.setTransform(77.4,47.2);

	this.shape_38 = new cjs.Shape();
	this.shape_38.graphics.f("#16A1FD").s().p("AhOAIIAjhMIB6ANIg6B8g");
	this.shape_38.setTransform(69.5,38.9);

	this.shape_39 = new cjs.Shape();
	this.shape_39.graphics.f("#16A1FD").s().p("AhJABIAag+IAhAAIBYB7g");
	this.shape_39.setTransform(82.9,40);

	this.shape_40 = new cjs.Shape();
	this.shape_40.graphics.f("#16A1FD").s().p("AgMgSIAZAlIgYAAg");
	this.shape_40.setTransform(79.4,31.1);

	this.shape_41 = new cjs.Shape();
	this.shape_41.graphics.f("#16A1FD").s().p("AgsA9IgrhtIB8hFIAuBIIAFBPIgnBUg");
	this.shape_41.setTransform(56,27.5);

	this.shape_42 = new cjs.Shape();
	this.shape_42.graphics.f("#16A1FD").s().p("Ag5AwIgDhSIA+gaIA5BPIACAqg");
	this.shape_42.setTransform(71.4,26.2);

	this.shape_43 = new cjs.Shape();
	this.shape_43.graphics.f("#16A1FD").s().p("AguAdIAzhHIAqA6IgsAbg");
	this.shape_43.setTransform(56.7,4.3);

	this.shape_44 = new cjs.Shape();
	this.shape_44.graphics.f("#16A1FD").s().p("AhEgvIAugeIBbCAIhAAbg");
	this.shape_44.setTransform(64.2,14.3);

	this.timeline.addTween(cjs.Tween.get({}).to({state:[{t:this.shape_44},{t:this.shape_43},{t:this.shape_42},{t:this.shape_41},{t:this.shape_40},{t:this.shape_39},{t:this.shape_38},{t:this.shape_37},{t:this.shape_36},{t:this.shape_35},{t:this.shape_34},{t:this.shape_33},{t:this.shape_32},{t:this.shape_31},{t:this.shape_30},{t:this.shape_29},{t:this.shape_28},{t:this.shape_27},{t:this.shape_26},{t:this.shape_25},{t:this.shape_24},{t:this.shape_23},{t:this.shape_22},{t:this.shape_21},{t:this.shape_20},{t:this.shape_19},{t:this.shape_18},{t:this.shape_17},{t:this.shape_16},{t:this.shape_15},{t:this.shape_14},{t:this.shape_13},{t:this.shape_12},{t:this.shape_11},{t:this.shape_10},{t:this.shape_9},{t:this.shape_8},{t:this.shape_7},{t:this.shape_6},{t:this.shape_5},{t:this.shape_4},{t:this.shape_3},{t:this.shape_2},{t:this.shape_1},{t:this.shape}]}).wait(1));

}).prototype = getMCSymbolPrototype(lib.Group_2, new cjs.Rectangle(0,0,113.9,97.3), null);


(lib.Group_1 = function(mode,startPosition,loop) {
	this.initialize(mode,startPosition,loop,{});

	// Layer_1
	this.shape = new cjs.Shape();
	this.shape.graphics.f("#1079BE").s().p("AhQAdIA8hlIBoA8QgJAkgPAwIiPABg");
	this.shape.setTransform(86.2,53.9);

	this.shape_1 = new cjs.Shape();
	this.shape_1.graphics.f("#1079BE").s().p("AhOAoIgShKIBzgnIBOArIg8Bog");
	this.shape_1.setTransform(73.8,49.3);

	this.shape_2 = new cjs.Shape();
	this.shape_2.graphics.f("#1079BE").s().p("AgHgoIAmAFQgYAjgmApg");
	this.shape_2.setTransform(75.1,91.5);

	this.shape_3 = new cjs.Shape();
	this.shape_3.graphics.f("#1079BE").s().p("Ag7BHIA7iTIA8AaQggBAgtA+g");
	this.shape_3.setTransform(80.5,79.6);

	this.shape_4 = new cjs.Shape();
	this.shape_4.graphics.f("#1079BE").s().p("AgoAhIgdhaICLAAQgVA6gcA5g");
	this.shape_4.setTransform(84.9,67.9);

	this.shape_5 = new cjs.Shape();
	this.shape_5.graphics.f("#1079BE").s().p("AgNgeIAjAUIgrApg");
	this.shape_5.setTransform(67.6,99.1);

	this.shape_6 = new cjs.Shape();
	this.shape_6.graphics.f("#1079BE").s().p("AgiAxIgIh6IBVArIgdBog");
	this.shape_6.setTransform(69.4,90.3);

	this.shape_7 = new cjs.Shape();
	this.shape_7.graphics.f("#1079BE").s().p("AhfgWICmgiIAZBQIh1Ahg");
	this.shape_7.setTransform(70.3,68.9);

	this.shape_8 = new cjs.Shape();
	this.shape_8.graphics.f("#1079BE").s().p("AhugFIBqg2IByAhIABAyIipAkg");
	this.shape_8.setTransform(66.2,60);

	this.shape_9 = new cjs.Shape();
	this.shape_9.graphics.f("#1079BE").s().p("AhIAbIAkhAIBtghIg6CNg");
	this.shape_9.setTransform(72.3,79.3);

	this.shape_10 = new cjs.Shape();
	this.shape_10.graphics.f("#1079BE").s().p("Ag/AfIBPhUIAwAtIghA+g");
	this.shape_10.setTransform(61.4,76);

	this.shape_11 = new cjs.Shape();
	this.shape_11.graphics.f("#1079BE").s().p("AgTBbIhNhKIBshzIBVBrIhVBag");
	this.shape_11.setTransform(52.7,68.9);

	this.shape_12 = new cjs.Shape();
	this.shape_12.graphics.f("#1079BE").s().p("AgOA/Ig5hNIABgKICPgoIgKBFQghAfgoAdg");
	this.shape_12.setTransform(58.2,102.5);

	this.shape_13 = new cjs.Shape();
	this.shape_13.graphics.f("#1079BE").s().p("AhggUIAshSICLAiIAKCBIiNArg");
	this.shape_13.setTransform(55.6,89);

	this.shape_14 = new cjs.Shape();
	this.shape_14.graphics.f("#1079BE").s().p("AArBAIhah7IAAAAIgBgCIAAgBIgDgDIAxALIA2B4g");
	this.shape_14.setTransform(45.3,92.7);

	this.shape_15 = new cjs.Shape();
	this.shape_15.graphics.f("#1079BE").s().p("AgOgSQAdgFAAABQgBACAAArg");
	this.shape_15.setTransform(31.2,73.4);

	this.shape_16 = new cjs.Shape();
	this.shape_16.graphics.f("#1079BE").s().p("AgWBCIg6hRIgBg9IBZADIBKBHIguBPg");
	this.shape_16.setTransform(41.6,78.5);

	this.shape_17 = new cjs.Shape();
	this.shape_17.graphics.f("#1079BE").s().p("AgzAzIgpgvIBhg6IBYAyIg1A7g");
	this.shape_17.setTransform(38.5,64.9);

	this.shape_18 = new cjs.Shape();
	this.shape_18.graphics.f("#1079BE").s().p("AgvgoIASgFIBNBVIgiAGg");
	this.shape_18.setTransform(27.8,66.3);

	this.shape_19 = new cjs.Shape();
	this.shape_19.graphics.f("#1079BE").s().p("Ag3AeIgYhAICNghIASBNIhkA6g");
	this.shape_19.setTransform(30.6,57.8);

	this.shape_20 = new cjs.Shape();
	this.shape_20.graphics.f("#1079BE").s().p("Ag+AWIgPhPIBCgOIBZBbIgyA1g");
	this.shape_20.setTransform(45.6,56.6);

	this.shape_21 = new cjs.Shape();
	this.shape_21.graphics.f("#1079BE").s().p("AgegYIAjgLIAaBCIgSAFg");
	this.shape_21.setTransform(21.1,57.8);

	this.shape_22 = new cjs.Shape();
	this.shape_22.graphics.f("#1079BE").s().p("AgugHIgFgcIApg0IA+CmIgeAJg");
	this.shape_22.setTransform(15.3,45.7);

	this.shape_23 = new cjs.Shape();
	this.shape_23.graphics.f("#1079BE").s().p("AhhgUIBbg3IBZAZIAQBMIhoAyg");
	this.shape_23.setTransform(55.1,51);

	this.shape_24 = new cjs.Shape();
	this.shape_24.graphics.f("#1079BE").s().p("AhJhJIAegIIB1BsIhcA3g");
	this.shape_24.setTransform(46.2,40.3);

	this.shape_25 = new cjs.Shape();
	this.shape_25.graphics.f("#1079BE").s().p("AgVhRIBJgTIA4CaIjXAvg");
	this.shape_25.setTransform(32.8,43.4);

	this.shape_26 = new cjs.Shape();
	this.shape_26.graphics.f("#1079BE").s().p("AhIgoIAqg9IBKgYIAdBHIhVCzg");
	this.shape_26.setTransform(22.2,40.4);

	this.shape_27 = new cjs.Shape();
	this.shape_27.graphics.f("#1079BE").s().p("AgVA+IhEhfICBgrIAyAtIhSBrg");
	this.shape_27.setTransform(9,33.5);

	this.shape_28 = new cjs.Shape();
	this.shape_28.graphics.f("#1079BE").s().p("Ag8gBIBqgjIAPAzIhLAWg");
	this.shape_28.setTransform(20.3,25.7);

	this.shape_29 = new cjs.Shape();
	this.shape_29.graphics.f("#1079BE").s().p("AhXgnIBhgjIBOBaIgUAfIhwAcg");
	this.shape_29.setTransform(34.8,26.8);

	this.shape_30 = new cjs.Shape();
	this.shape_30.graphics.f("#1079BE").s().p("AhfgCIBLhiIB0BXIhJByg");
	this.shape_30.setTransform(52.1,32.1);

	this.shape_31 = new cjs.Shape();
	this.shape_31.graphics.f("#1079BE").s().p("AhjAvIBMhxIBigFIAZBqIhxAlg");
	this.shape_31.setTransform(65.1,38);

	this.shape_32 = new cjs.Shape();
	this.shape_32.graphics.f("#1079BE").s().p("AhPAVIBEh5IBbBMQgGBBgOA8g");
	this.shape_32.setTransform(88.8,41.8);

	this.shape_33 = new cjs.Shape();
	this.shape_33.graphics.f("#1079BE").s().p("AhDgdIAHgJIBCgWIA+AmIg8BTg");
	this.shape_33.setTransform(43.7,21.8);

	this.shape_34 = new cjs.Shape();
	this.shape_34.graphics.f("#1079BE").s().p("AhYAeIA2hcIB8A5IgnBAIhdAEg");
	this.shape_34.setTransform(67.4,23.8);

	this.shape_35 = new cjs.Shape();
	this.shape_35.graphics.f("#1079BE").s().p("AgwBLIgWhvIAjg+IBqAzIhNCSg");
	this.shape_35.setTransform(80.8,33.7);

	this.shape_36 = new cjs.Shape();
	this.shape_36.graphics.f("#1079BE").s().p("AgvAGIBKhTIATAOIACA9QAAAhgEAvg");
	this.shape_36.setTransform(92.5,30.1);

	this.shape_37 = new cjs.Shape();
	this.shape_37.graphics.f("#1079BE").s().p("AgFABIAJgLIACAVg");
	this.shape_37.setTransform(96.4,21.4);

	this.shape_38 = new cjs.Shape();
	this.shape_38.graphics.f("#1079BE").s().p("AhcASIBQhWIBpArIAAAFIhMBYg");
	this.shape_38.setTransform(87.4,21.3);

	this.shape_39 = new cjs.Shape();
	this.shape_39.graphics.f("#1079BE").s().p("Ag9ARIArhNIBQAiIg3BXg");
	this.shape_39.setTransform(56.9,19.9);

	this.shape_40 = new cjs.Shape();
	this.shape_40.graphics.f("#1079BE").s().p("AgogCIBQgbIgbA7g");
	this.shape_40.setTransform(49.7,15.6);

	this.shape_41 = new cjs.Shape();
	this.shape_41.graphics.f("#1079BE").s().p("AhrgSIACgIICJgrIBMCLg");
	this.shape_41.setTransform(65.9,14.8);

	this.shape_42 = new cjs.Shape();
	this.shape_42.graphics.f("#1079BE").s().p("AhNg0IByglIApBlIhPBOg");
	this.shape_42.setTransform(77.8,12.6);

	this.shape_43 = new cjs.Shape();
	this.shape_43.graphics.f("#1079BE").s().p("AgfAfIgnhmIBsAsIAeBOIADAVg");
	this.shape_43.setTransform(89.5,10.6);

	this.shape_44 = new cjs.Shape();
	this.shape_44.graphics.f("#1079BE").s().p("AgJgRIAHgMQAEAWAIAlg");
	this.shape_44.setTransform(94.8,9.4);

	this.shape_45 = new cjs.Shape();
	this.shape_45.graphics.f("#1079BE").s().p("Ag0gFIBZgdIAQA1IgJAQg");
	this.shape_45.setTransform(88.9,3.5);

	this.timeline.addTween(cjs.Tween.get({}).to({state:[{t:this.shape_45},{t:this.shape_44},{t:this.shape_43},{t:this.shape_42},{t:this.shape_41},{t:this.shape_40},{t:this.shape_39},{t:this.shape_38},{t:this.shape_37},{t:this.shape_36},{t:this.shape_35},{t:this.shape_34},{t:this.shape_33},{t:this.shape_32},{t:this.shape_31},{t:this.shape_30},{t:this.shape_29},{t:this.shape_28},{t:this.shape_27},{t:this.shape_26},{t:this.shape_25},{t:this.shape_24},{t:this.shape_23},{t:this.shape_22},{t:this.shape_21},{t:this.shape_20},{t:this.shape_19},{t:this.shape_18},{t:this.shape_17},{t:this.shape_16},{t:this.shape_15},{t:this.shape_14},{t:this.shape_13},{t:this.shape_12},{t:this.shape_11},{t:this.shape_10},{t:this.shape_9},{t:this.shape_8},{t:this.shape_7},{t:this.shape_6},{t:this.shape_5},{t:this.shape_4},{t:this.shape_3},{t:this.shape_2},{t:this.shape_1},{t:this.shape}]}).wait(1));

}).prototype = getMCSymbolPrototype(lib.Group_1, new cjs.Rectangle(0,0,97.3,109), null);


(lib.Group = function(mode,startPosition,loop) {
	this.initialize(mode,startPosition,loop,{});

	// Layer_1
	this.shape = new cjs.Shape();
	this.shape.graphics.f("#0B517F").s().p("AgGAMIACgYIALAYg");
	this.shape.setTransform(88,56);

	this.shape_1 = new cjs.Shape();
	this.shape_1.graphics.f("#0B517F").s().p("AguAeIgRhKIAzgRQArA8AhA/g");
	this.shape_1.setTransform(80,46.4);

	this.shape_2 = new cjs.Shape();
	this.shape_2.graphics.f("#0B517F").s().p("AADArIgchaIARAAQAUAvAOAsIgMAEg");
	this.shape_2.setTransform(90,62.9);

	this.shape_3 = new cjs.Shape();
	this.shape_3.graphics.f("#0B517F").s().p("AhfgWICmgiIAZBQIh1Aig");
	this.shape_3.setTransform(79.8,65);

	this.shape_4 = new cjs.Shape();
	this.shape_4.graphics.f("#0B517F").s().p("AhugFIBrg2IBxAhIABAyIipAkg");
	this.shape_4.setTransform(75.7,56.1);

	this.shape_5 = new cjs.Shape();
	this.shape_5.graphics.f("#0B517F").s().p("Ag/AnIBOhTIAxAsIgHAOIhgAfg");
	this.shape_5.setTransform(70.9,71.1);

	this.shape_6 = new cjs.Shape();
	this.shape_6.graphics.f("#0B517F").s().p("AgTBbIhNhKIBthzIBUBrIhWBag");
	this.shape_6.setTransform(62.3,65);

	this.shape_7 = new cjs.Shape();
	this.shape_7.graphics.f("#0B517F").s().p("AgRgTIA4APIhNAXg");
	this.shape_7.setTransform(61.7,76.7);

	this.shape_8 = new cjs.Shape();
	this.shape_8.graphics.f("#0B517F").s().p("AgQgNIAuAJIg7ASg");
	this.shape_8.setTransform(45.1,82.3);

	this.shape_9 = new cjs.Shape();
	this.shape_9.graphics.f("#0B517F").s().p("AgtglIBwADIgQAiIh0Amg");
	this.shape_9.setTransform(35.9,84.1);

	this.shape_10 = new cjs.Shape();
	this.shape_10.graphics.f("#0B517F").s().p("AgogLIACgEIBPAFIhRAag");
	this.shape_10.setTransform(4.1,95.8);

	this.shape_11 = new cjs.Shape();
	this.shape_11.graphics.f("#0B517F").s().p("AhJA1IgBhGICVgqIAABnIgxAQg");
	this.shape_11.setTransform(8.1,87.9);

	this.shape_12 = new cjs.Shape();
	this.shape_12.graphics.f("#0B517F").s().p("AgeA7IgyhbQChgigBAEQgBADABA9IAAA9g");
	this.shape_12.setTransform(34.2,73.5);

	this.shape_13 = new cjs.Shape();
	this.shape_13.graphics.f("#0B517F").s().p("AhPA8IgCiDIBaADIBJBFIgbAxIhEAWg");
	this.shape_13.setTransform(51.1,74.1);

	this.shape_14 = new cjs.Shape();
	this.shape_14.graphics.f("#0B517F").s().p("AgzAzIgpgvIBhg6IBYAyIg1A7g");
	this.shape_14.setTransform(48,61);

	this.shape_15 = new cjs.Shape();
	this.shape_15.graphics.f("#0B517F").s().p("AhCgnICGgRIgUBLIhzAmg");
	this.shape_15.setTransform(23.3,86.4);

	this.shape_16 = new cjs.Shape();
	this.shape_16.graphics.f("#0B517F").s().p("AhPghIBSgYIBOBVIihAeg");
	this.shape_16.setTransform(34,63.6);

	this.shape_17 = new cjs.Shape();
	this.shape_17.graphics.f("#0B517F").s().p("Ag3AeIgYhBICNggIASBNIhjA6g");
	this.shape_17.setTransform(40.1,53.9);

	this.shape_18 = new cjs.Shape();
	this.shape_18.graphics.f("#0B517F").s().p("Ag9AWIgQhPIBCgOIBZBbIgyA0g");
	this.shape_18.setTransform(55.1,52.6);

	this.shape_19 = new cjs.Shape();
	this.shape_19.graphics.f("#0B517F").s().p("AhJhCIBsgOIAoB6IiVAng");
	this.shape_19.setTransform(8.3,77);

	this.shape_20 = new cjs.Shape();
	this.shape_20.graphics.f("#0B517F").s().p("AhXg7IB7AHIAzBgIiGAQg");
	this.shape_20.setTransform(21.4,75.3);

	this.shape_21 = new cjs.Shape();
	this.shape_21.graphics.f("#0B517F").s().p("AgyAJIgFgbIBVgZIAZBBIhMAWg");
	this.shape_21.setTransform(28.1,54.7);

	this.shape_22 = new cjs.Shape();
	this.shape_22.graphics.f("#0B517F").s().p("Ag9A6IgEhuIBpgMIAaAhIAABgg");
	this.shape_22.setTransform(18.6,62.7);

	this.shape_23 = new cjs.Shape();
	this.shape_23.graphics.f("#0B517F").s().p("Ag5A3IAAhdIBvgdIAEB6IhrANg");
	this.shape_23.setTransform(5.8,62.6);

	this.shape_24 = new cjs.Shape();
	this.shape_24.graphics.f("#0B517F").s().p("AhfAaIAEhgIA6AAQA/AGBCAOIgxB5g");
	this.shape_24.setTransform(11.8,7.5);

	this.shape_25 = new cjs.Shape();
	this.shape_25.graphics.f("#0B517F").s().p("AgJg5IARABIACAAIgGBlIgNANg");
	this.shape_25.setTransform(1,5.8);

	this.shape_26 = new cjs.Shape();
	this.shape_26.graphics.f("#0B517F").s().p("AhRAEIAAhOIATgTICQAwIhtCLg");
	this.shape_26.setTransform(8.2,20.2);

	this.shape_27 = new cjs.Shape();
	this.shape_27.graphics.f("#0B517F").s().p("AgYBOIAAieIAxBUIgtBNg");
	this.shape_27.setTransform(2.5,30.2);

	this.shape_28 = new cjs.Shape();
	this.shape_28.graphics.f("#0B517F").s().p("AhEghIB2gPIATBXIhgAKg");
	this.shape_28.setTransform(15.2,51.7);

	this.shape_29 = new cjs.Shape();
	this.shape_29.graphics.f("#0B517F").s().p("Ag3gnIBKgHIAkBCIhuAbg");
	this.shape_29.setTransform(5.6,53.1);

	this.shape_30 = new cjs.Shape();
	this.shape_30.graphics.f("#0B517F").s().p("AhjAMIA4hhICBBEIAOBPIjHAXg");
	this.shape_30.setTransform(10,39.8);

	this.shape_31 = new cjs.Shape();
	this.shape_31.graphics.f("#0B517F").s().p("AgzgrIAqg0IA9CmIhOAZg");
	this.shape_31.setTransform(24.8,42.5);

	this.shape_32 = new cjs.Shape();
	this.shape_32.graphics.f("#0B517F").s().p("AhigUIBdg3IBZAZIAOBMIhmAyg");
	this.shape_32.setTransform(64.6,47);

	this.shape_33 = new cjs.Shape();
	this.shape_33.graphics.f("#0B517F").s().p("AhJhJIAegIIB1BrIhcA4g");
	this.shape_33.setTransform(55.7,36.4);

	this.shape_34 = new cjs.Shape();
	this.shape_34.graphics.f("#0B517F").s().p("AgVhRIBJgTIA3CaIjVAvg");
	this.shape_34.setTransform(42.3,39.4);

	this.shape_35 = new cjs.Shape();
	this.shape_35.graphics.f("#0B517F").s().p("AhIgoIAqg+IBKgWIAdBGIhVCzg");
	this.shape_35.setTransform(31.7,36.5);

	this.shape_36 = new cjs.Shape();
	this.shape_36.graphics.f("#0B517F").s().p("AhrAhIBsiGIBrBgIhSBrg");
	this.shape_36.setTransform(16.8,27);

	this.shape_37 = new cjs.Shape();
	this.shape_37.graphics.f("#0B517F").s().p("AhbgbQACABBNgTIBLgSIAdBqIhLAVg");
	this.shape_37.setTransform(26.6,18.9);

	this.shape_38 = new cjs.Shape();
	this.shape_38.graphics.f("#0B517F").s().p("AgXg8QAwAMAlANIANAQIAAArIiWAlg");
	this.shape_38.setTransform(24.9,8.8);

	this.shape_39 = new cjs.Shape();
	this.shape_39.graphics.f("#0B517F").s().p("AhEg2IAGgDQBGAcA9AgIgLAOIhsApg");
	this.shape_39.setTransform(40.2,11.9);

	this.shape_40 = new cjs.Shape();
	this.shape_40.graphics.f("#0B517F").s().p("AhXgmIBigjIBNBZIgTAfIhxAbg");
	this.shape_40.setTransform(44.3,22.8);

	this.shape_41 = new cjs.Shape();
	this.shape_41.graphics.f("#0B517F").s().p("AhWgJIA/hTQA5ArA1A2Ig3BYg");
	this.shape_41.setTransform(60.8,28.9);

	this.shape_42 = new cjs.Shape();
	this.shape_42.graphics.f("#0B517F").s().p("AhDAdIA5hTQAsAwAiAtIgyAQg");
	this.shape_42.setTransform(71.4,35.7);

	this.shape_43 = new cjs.Shape();
	this.shape_43.graphics.f("#0B517F").s().p("Ag2giIAPgVQAyAdAtAgIgjAyg");
	this.shape_43.setTransform(52,18.4);

	this.timeline.addTween(cjs.Tween.get({}).to({state:[{t:this.shape_43},{t:this.shape_42},{t:this.shape_41},{t:this.shape_40},{t:this.shape_39},{t:this.shape_38},{t:this.shape_37},{t:this.shape_36},{t:this.shape_35},{t:this.shape_34},{t:this.shape_33},{t:this.shape_32},{t:this.shape_31},{t:this.shape_30},{t:this.shape_29},{t:this.shape_28},{t:this.shape_27},{t:this.shape_26},{t:this.shape_25},{t:this.shape_24},{t:this.shape_23},{t:this.shape_22},{t:this.shape_21},{t:this.shape_20},{t:this.shape_19},{t:this.shape_18},{t:this.shape_17},{t:this.shape_16},{t:this.shape_15},{t:this.shape_14},{t:this.shape_13},{t:this.shape_12},{t:this.shape_11},{t:this.shape_10},{t:this.shape_9},{t:this.shape_8},{t:this.shape_7},{t:this.shape_6},{t:this.shape_5},{t:this.shape_4},{t:this.shape_3},{t:this.shape_2},{t:this.shape_1},{t:this.shape}]}).wait(1));

}).prototype = getMCSymbolPrototype(lib.Group, new cjs.Rectangle(0,0,92.7,97.4), null);


(lib.clockai = function(mode,startPosition,loop) {
	this.initialize(mode,startPosition,loop,{});

	// Layer 3
	this.shape = new cjs.Shape();
	this.shape.graphics.f("#FFFFFF").s().p("AgXAwQgJgEgHgJQgOgQACgSQACgQASgPIAHgFQATgQAQAAQASgBANAQQAMAPgDAQQgDAPgTAQIgLAIIgigpQgIAHgBAHQgCAHAGAIQAJAKAMABIgFATIgBAAQgIAAgJgEgAANgbQgGABgIAHIARAUIACgBQAHgGACgFQACgFgFgGQgEgFgGAAIgBAAg");
	this.shape.setTransform(79.6,73.6);

	this.shape_1 = new cjs.Shape();
	this.shape_1.graphics.f("#FFFFFF").s().p("AgRAyQgNgCgLgOQgIgJgCgJQgCgKADgIQADgJAHgGIARATQgGAEAAAFQgBAFAFAGQAHAIAIgGQACgDAAgEQAAgEgDgNQgGgQACgKQABgKAJgHQAKgJANADQANABALANQALANAAAOQAAANgMAKIgSgUQAKgJgHgIQgDgEgEAAQgDAAgEADQgDACAAAEQAAAEAEAMQAFAQgBALQgBAKgKAIQgHAHgJAAIgHgBg");
	this.shape_1.setTransform(73.5,66.1);

	this.shape_2 = new cjs.Shape();
	this.shape_2.graphics.f("#FFFFFF").s().p("Ag+AiIBQhCIARAVIhQBDgAAugYQgFAAgFgFQgEgGAAgGQABgGAFgEQAFgEAGABQAFAAAFAGQAEAFAAAFQgBAGgFAFQgEADgFAAIgCAAg");
	this.shape_2.setTransform(70.3,59.4);

	this.shape_3 = new cjs.Shape();
	this.shape_3.graphics.f("#FFFFFF").s().p("AgeAlIAFgIQgMACgJgKQgIgKABgMQABgMALgJQANgLAOACQANADAMAPIAFAGIAHgFQAFgFABgDQABgEgDgEQgHgIgJAIIgQgVQAKgJAOACQAOACALANQALANgBAOQAAAMgNAKIgnAgQgJAJgDAIIgCABgAgWgCQgJAGAGAIQADADADABIAHABIAQgOIgEgEQgEgFgGgBIgBAAQgFAAgGAFg");
	this.shape_3.setTransform(64.2,55.8);

	this.shape_4 = new cjs.Shape();
	this.shape_4.graphics.f("#FFFFFF").s().p("Ag5AXIBQhCIARAUIgIAIQAPgEAGAIIAFAIIgVAQIgGgIQgHgHgLACIg0Asg");
	this.shape_4.setTransform(59.4,48.2);

	this.shape_5 = new cjs.Shape();
	this.shape_5.graphics.f("#FFFFFF").s().p("AAYA5QgRgCgNgRIgMgOIgnAgIgSgWIBshaIAfAlQAOAQgCARQgCASgQANQgOAMgPAAIgFAAgAAAAIIAMAPQAFAHAHgBQAHABAIgHQAIgHACgHQACgIgFgFIgNgPg");
	this.shape_5.setTransform(54,38.5);

	this.shape_6 = new cjs.Shape();
	this.shape_6.graphics.f("#FFFFFF").s().p("Ag3glIAYgJIAFAKQAEgQAOgFQAZgKAOAkIAZA/IgZAKIgZg+QgDgIgDgDQgEgCgGACQgHADgCAIIAbBHIgZAKg");
	this.shape_6.setTransform(71.4,106.6);

	this.shape_7 = new cjs.Shape();
	this.shape_7.graphics.f("#FFFFFF").s().p("AgNAyQgQgJgJgWIgCgHQgJgVAGgRQAFgRATgHQATgIAOAJQAQAJAIAXIADAGQAJAVgGARQgGARgSAHQgJAEgIAAQgIAAgIgFgAgLgdQgGADgBAIQgCAIAFAMIADAHQAKAaAOgFQANgFgIgXIgEgKQgFgNgHgFQgDgEgEAAIgFABg");
	this.shape_7.setTransform(62.2,109.9);

	this.shape_8 = new cjs.Shape();
	this.shape_8.graphics.f("#FFFFFF").s().p("AgZgaIAZgKIAmBiIgaAKgAgdgqQgFgDgCgGQgCgGACgFQACgFAHgDQAGgCAFACQAGACACAGQACAGgCAGQgCAFgHADIgGABIgGgBg");
	this.shape_8.setTransform(54.6,110.9);

	this.shape_9 = new cjs.Shape();
	this.shape_9.graphics.f("#FFFFFF").s().p("AgEAqIgVgzIgLAEIgIgTIAMgFIgJgYIAagKIAJAYIAMgFIAHAUIgNAFIATAwQADAFACACQACACAFgCIAGgDIAIAUQgGAFgIADQgGADgFAAQgRAAgHgWg");
	this.shape_9.setTransform(49.9,113.3);

	this.shape_10 = new cjs.Shape();
	this.shape_10.graphics.f("#FFFFFF").s().p("AgZgaIAZgKIAmBiIgaAKgAgdgqQgFgDgCgGQgCgGACgFQACgFAHgDQAGgCAFACQAGACACAGQACAGgCAGQgCAFgHADIgGABIgGgBg");
	this.shape_10.setTransform(44.2,115);

	this.shape_11 = new cjs.Shape();
	this.shape_11.graphics.f("#FFFFFF").s().p("AgEAqIgVgzIgLAEIgIgTIAMgFIgJgYIAagKIAJAYIAMgFIAHAUIgNAFIATAwQADAFACACQACACAFgCIAGgDIAIAUQgGAFgIADQgGADgFAAQgRAAgHgWg");
	this.shape_11.setTransform(39.5,117.3);

	this.shape_12 = new cjs.Shape();
	this.shape_12.graphics.f("#FFFFFF").s().p("AgOAzQgQgIgIgWIgDgIQgJgWAFgQQAFgRATgIQATgHANAIQAOAJAJAXIAFANIgyATQAFAKAGAEQAFADAJgEQANgFAFgMIAQAMQgCAIgIAIQgHAIgLAEQgKADgJAAQgHAAgIgDgAgMgcQgHACgCAHQgBAFAEALIAYgKIAAgCQgEgJgDgDQgDgCgDAAIgFABg");
	this.shape_12.setTransform(32.7,121.5);

	this.shape_13 = new cjs.Shape();
	this.shape_13.graphics.f("#FFFFFF").s().p("Ag2g2IAugSQASgIAQAIQARAIAIATQAHAUgHAPQgHAPgVAIIgRAHIASAvIgaAKgAAAgyIgSAIIAQApIARgHQAIgDACgHQADgHgEgJQgEgKgHgEQgEgDgEAAIgFABg");
	this.shape_13.setTransform(20.5,124.2);

	this.shape_14 = new cjs.Shape();
	this.shape_14.graphics.f("#FFFFFF").s().p("AggA/QgJgNABgYIAAgIQAAgZAIgNQAKgNAQAAQAMAAAHALIAAg1IAcAAIAACVIgZAAIgCgKQgIAMgMAAQgRAAgJgNgAgJgEQgDAGgBAPIAAAIQAAAPADAHQAEAGAHAAQAIAAAEgHIAAgvQgDgJgJAAQgGAAgEAGg");
	this.shape_14.setTransform(129.7,167.8);

	this.shape_15 = new cjs.Shape();
	this.shape_15.graphics.f("#FFFFFF").s().p("AgaA2IABhpIAZAAIABAMQAGgOAMAAIAIACIgBAbIgJgBQgMAAgEAKIAABFg");
	this.shape_15.setTransform(121.9,169.8);

	this.shape_16 = new cjs.Shape();
	this.shape_16.graphics.f("#FFFFFF").s().p("AAAA3QgTAAgMgOQgLgOAAgYIAAgFQABgZALgNQALgOATAAQAUABAMANQALAOAAAYIAAAGQAAAYgMAOQgKANgUAAIgBAAgAgKgYQgEAHAAAPIAAAFQAAAdAOAAQAOAAABgXIAAgLQABgOgEgHQgEgIgIAAQgGAAgEAHg");
	this.shape_16.setTransform(113.3,169.8);

	this.shape_17 = new cjs.Shape();
	this.shape_17.graphics.f("#FFFFFF").s().p("AAQBHIgQhTIgQBTIgdAAIgZiNIAdAAIANBYIARhYIAXAAIAQBYIAPhYIAcAAIgaCNg");
	this.shape_17.setTransform(100.9,168);

	this.shape_18 = new cjs.Shape();
	this.shape_18.graphics.f("#FFFFFF").s().p("AAEA3QgUAAgMgNQgMgNAAgXIAAgIQAAgZALgNQALgOAUAAQAUABAKAMQAKANgBAZIAAANIg2gBQABAMAFAFQAEAGAJAAQAOAAAJgJIALAQQgFAIgKAEQgJAEgKAAIgCAAgAgIgaQgDAFgBAMIAbAAIAAgCQAAgKgDgFQgDgFgIAAQgGAAgDAFg");
	this.shape_18.setTransform(84,169.7);

	this.shape_19 = new cjs.Shape();
	this.shape_19.graphics.f("#FFFFFF").s().p("AAMBLIAAhDQAAgIgDgFQgDgEgGAAQgHAAgEAHIAABNIgcAAIABiVIAbAAIAAA1QAJgLAMAAQAPABAHAKQAIAKAAATIgBBDg");
	this.shape_19.setTransform(74.2,167.5);

	this.shape_20 = new cjs.Shape();
	this.shape_20.graphics.f("#FFFFFF").s().p("AgOAkIAAg3IgMAAIAAgVIAMAAIAAgaIAbAAIAAAaIAOAAIAAAVIgOAAIgBAzQAAAHACACQABACAGAAIAGAAIAAAVQgHADgIAAQgaAAAAgfg");
	this.shape_20.setTransform(66.2,168.4);

	this.shape_21 = new cjs.Shape();
	this.shape_21.graphics.f("#FFFFFF").s().p("AggBJIAAgXIADABQAGAAAEgDQADgCACgHIACgHIgehpIAdAAIANA7IANg7IAeABIgjB3QgIAbgVAAIgLgBg");
	this.shape_21.setTransform(112.8,149.6);

	this.shape_22 = new cjs.Shape();
	this.shape_22.graphics.f("#FFFFFF").s().p("AgeAuQgJgJAAgOQAAgQAKgJQALgJATAAIAIAAIAAgIQABgHgDgDQgDgDgEAAQgKAAAAAMIgbAAQAAgPALgJQALgKAQAAQARABAJAJQAKAJAAARIgBAwQABAOAEAIIAAABIgcAAIgDgIQgHAKgMAAQgNAAgIgJgAgIAJQgDAFAAAHQgBAMAKAAQADAAAEgCIAEgEIAAgWIgHAAQgFAAgFAEg");
	this.shape_22.setTransform(103.7,147.4);

	this.shape_23 = new cjs.Shape();
	this.shape_23.graphics.f("#FFFFFF").s().p("AgaA2IABhpIAZAAIABAMQAGgOAMAAIAHACIAAAbIgJgBQgLAAgFAKIAABFg");
	this.shape_23.setTransform(95.9,147.3);

	this.shape_24 = new cjs.Shape();
	this.shape_24.graphics.f("#FFFFFF").s().p("AgvBHIABiNIAwABQAUgBANANQANAOAAAUQAAAWgNALQgNAMgWgBIgSAAIAAAygAgSgCIAUAAQAHABAFgGQAFgFgBgLQABgKgFgHQgFgFgHgBIgUgBg");
	this.shape_24.setTransform(86.8,145.6);

	this.shape_25 = new cjs.Shape();
	this.shape_25.graphics.f("#FFFFFF").s().p("AgCA2IAWg+QADgJgBgFQgBgFgGgCQgHgCgHAGIgZBGIgagJIAkhiIAXAJIgDAKQAOgJAOAGQAZAJgMAlIgYA/g");
	this.shape_25.setTransform(174.9,127.3);

	this.shape_26 = new cjs.Shape();
	this.shape_26.graphics.f("#FFFFFF").s().p("AgSA0QgTgHgGgRQgGgRAJgVIACgHQAIgWAPgKQAPgIATAHQATAHAFAQQAHARgJAWIgCAGQgIAXgPAIQgJAGgJAAQgIAAgIgDgAgBgaQgGAFgFAOIgCAGQgKAaAOAFQAMAFAKgWIADgLQAFgNgBgHQgBgIgHgDIgFgBQgEAAgDAEg");
	this.shape_26.setTransform(166,123.9);

	this.shape_27 = new cjs.Shape();
	this.shape_27.graphics.f("#FFFFFF").s().p("AgkA/IAkhiIAZAJIgjBjgAARgqQgHgDgCgFQgCgFACgGQACgGAFgDQAFgDAHADQAGACADAGQACAFgCAGQgCAGgFADIgGABIgGgBg");
	this.shape_27.setTransform(159.5,119.5);

	this.shape_28 = new cjs.Shape();
	this.shape_28.graphics.f("#FFFFFF").s().p("AgPBCQgZgJAKgdIATg0IgMgEIAHgUIAMAEIAIgYIAaAKIgJAYIANAFIgHATIgNgFIgRAxQgCAGABACQABADADACIAGABIgGAVQgHAAgIgDg");
	this.shape_28.setTransform(154.5,118.4);

	this.shape_29 = new cjs.Shape();
	this.shape_29.graphics.f("#FFFFFF").s().p("AgkA/IAkhiIAZAJIgjBjgAARgqQgHgDgCgFQgCgFACgGQACgGAFgDQAFgDAHADQAGACADAGQACAFgCAGQgCAGgFADIgGABIgGgBg");
	this.shape_29.setTransform(149,115.7);

	this.shape_30 = new cjs.Shape();
	this.shape_30.graphics.f("#FFFFFF").s().p("AgPBCQgZgJAKgdIATg0IgMgEIAHgUIAMAEIAIgYIAaAKIgJAYIANAFIgHATIgNgFIgRAxQgCAGABACQABADADACIAGABIgGAVQgHAAgIgDg");
	this.shape_30.setTransform(144,114.5);

	this.shape_31 = new cjs.Shape();
	this.shape_31.graphics.f("#FFFFFF").s().p("AgOA0QgUgHgHgRQgHgQAIgVIADgIQAIgXAPgJQAOgJAUAHQASAHAFAPQAFAPgIAXIgFANIgygTQgEALADAHQACAHAJADQAMAFAMgGIAEATQgGAFgLABIgEAAQgIAAgIgDgAAAgcQgEADgFALIAZAJIABgCQADgJAAgGQgCgFgHgDIgEgBQgEAAgDADg");
	this.shape_31.setTransform(136,113.1);

	this.shape_32 = new cjs.Shape();
	this.shape_32.graphics.f("#FFFFFF").s().p("Ag+A+IAxiFIAtARQAUAHAHARQAHAQgHATQgHAUgQAHQgPAHgVgHIgSgIIgRAvgAgJACIASAHQAHADAGgEQAHgDADgIQAEgLgDgHQgBgIgIgCIgTgIg");
	this.shape_32.setTransform(126.2,106.6);

	this.shape_33 = new cjs.Shape();
	this.shape_33.graphics.f("#FFFFFF").s().p("AgYAkIgGgGQgTgQgDgQQgDgSANgPQAOgPAQAAQAPABATARIAKAJIgjAoQAIAHAHAAQAHAAAGgHQAKgKgBgMIATACQACAIgDAKQgDAKgHAJQgOAQgRABIgCAAQgQAAgRgPgAgZgUQgEAGACAGQABAFAIAHIASgUIgBgBQgIgGgFgBIgCAAQgFAAgEAEg");
	this.shape_33.setTransform(147.3,41.6);

	this.shape_34 = new cjs.Shape();
	this.shape_34.graphics.f("#FFFFFF").s().p("AAFAzQgIgCgHgGIAQgUQAGAFAEAAQAFAAAFgGQAHgIgHgHQgDgCgEAAQgFABgMAGQgPAIgJAAQgLAAgIgHQgKgJgBgNQABgNAKgNQAMgNANgCQAOgCAKAKIgRAVQgKgJgHAIQgDAEABAEQAAAEAEADQACACAEAAQAEgBAMgGQAOgIALgBQAKAAAKAIQAKAKAAAMQgBAOgLANQgIAJgJAEQgHACgGAAIgGAAg");
	this.shape_34.setTransform(140.9,48.8);

	this.shape_35 = new cjs.Shape();
	this.shape_35.graphics.f("#FFFFFF").s().p("AgigMIASgVIBOBEIgSAVgAg4gdQgFgEAAgGQgBgGAFgFQAEgFAGAAQAGgBAFAEQAFAEAAAGQABAGgFAFQgEAGgGAAIgBAAQgGAAgEgEg");
	this.shape_35.setTransform(134.9,53);

	this.shape_36 = new cjs.Shape();
	this.shape_36.graphics.f("#FFFFFF").s().p("AgPAsQgNgLAAgOQAAgOAOgOIAFgGIgGgGQgGgEgDgBQgFAAgCAEQgIAIAJAIIgSATQgLgJABgOQAAgOALgNQALgNANgBQANgBAMALIAlAgQALAJAIABIABABIgSAVIgIgDQADAMgIAKQgJAKgMAAIgBAAQgMAAgJgIgAgBAAQgFAFABAGQABAGAFAFQAJAIAFgHQADgEABgDQABgEgBgDIgQgNg");
	this.shape_36.setTransform(132.3,59.7);

	this.shape_37 = new cjs.Shape();
	this.shape_37.graphics.f("#FFFFFF").s().p("AgwgOIARgTIAJAGQgFgNAHgJIAGgFIAUASIgGAHQgHAIAEAKIA0AtIgSAVg");
	this.shape_37.setTransform(125.7,65.4);

	this.shape_38 = new cjs.Shape();
	this.shape_38.graphics.f("#FFFFFF").s().p("Ag+gTIAgglQANgQASAAQASgBAPANQAQAOABASQACAQgPAQIgMAOIAlAhIgTAWgAgMgoIgNAPIAgAcIANgOQAFgGgBgHQgBgHgHgHQgIgHgIAAIAAAAQgHAAgFAFg");
	this.shape_38.setTransform(116.9,72);

	this.instance = new lib.Group();
	this.instance.parent = this;
	this.instance.setTransform(144.9,48.7,1,1,0,0,0,46.3,48.7);
	this.instance.alpha = 0.398;

	this.instance_1 = new lib.Group_1();
	this.instance_1.parent = this;
	this.instance_1.setTransform(147.5,122.8,1,1,0,0,0,48.6,54.5);
	this.instance_1.alpha = 0.398;

	this.instance_2 = new lib.Group_2();
	this.instance_2.parent = this;
	this.instance_2.setTransform(97.9,147.5,1,1,0,0,0,57,48.6);
	this.instance_2.alpha = 0.398;

	this.instance_3 = new lib.Group_3();
	this.instance_3.parent = this;
	this.instance_3.setTransform(48.6,122.7,1,1,0,0,0,48.6,54.4);
	this.instance_3.alpha = 0.398;

	this.instance_4 = new lib.Group_4();
	this.instance_4.parent = this;
	this.instance_4.setTransform(51.3,48.7,1,1,0,0,0,46.3,48.7);
	this.instance_4.alpha = 0.398;

	this.shape_39 = new cjs.Shape();
	this.shape_39.graphics.f("#06283F").s().p("Al9OIQiwhLiIiHQiIiIhKiwQhNi2AAjIQAAjGBNi3QBKiwCIiHQCIiICwhLQC2hNDHAAQDHAAC3BNQCwBLCICIQCICHBKCwQBNC3AADGQAADIhNC2QhKCwiICIQiICHiwBLQi3BNjHAAQjHAAi2hNg");
	this.shape_39.setTransform(98.1,98.1);

	this.timeline.addTween(cjs.Tween.get({}).to({state:[{t:this.shape_39},{t:this.instance_4},{t:this.instance_3},{t:this.instance_2},{t:this.instance_1},{t:this.instance},{t:this.shape_38},{t:this.shape_37},{t:this.shape_36},{t:this.shape_35},{t:this.shape_34},{t:this.shape_33},{t:this.shape_32},{t:this.shape_31},{t:this.shape_30},{t:this.shape_29},{t:this.shape_28},{t:this.shape_27},{t:this.shape_26},{t:this.shape_25},{t:this.shape_24},{t:this.shape_23},{t:this.shape_22},{t:this.shape_21},{t:this.shape_20},{t:this.shape_19},{t:this.shape_18},{t:this.shape_17},{t:this.shape_16},{t:this.shape_15},{t:this.shape_14},{t:this.shape_13},{t:this.shape_12},{t:this.shape_11},{t:this.shape_10},{t:this.shape_9},{t:this.shape_8},{t:this.shape_7},{t:this.shape_6},{t:this.shape_5},{t:this.shape_4},{t:this.shape_3},{t:this.shape_2},{t:this.shape_1},{t:this.shape}]}).wait(1));

}).prototype = p = new cjs.MovieClip();
p.nominalBounds = new cjs.Rectangle(-6,0,209.2,196.2);


// stage content:
(lib.prayer_clock = function(mode,startPosition,loop) {
	this.initialize(mode,startPosition,loop,{});

	// timeline functions:
	this.frame_0 = function() {
		var SecondHand=null
		
		
		this.StartButton.addEventListener("click", fl_MouseClickHandler.bind(this));
		this.StartButton.btnLabel.text = "Start";
		this.StartButton.btnLabel.mouseEnabled = false;
		
		var go=false
		var milliseconds=0
		function fl_MouseClickHandler()
		{
		    if (!SecondHand){
		        SecondHand=this
		    }
		    go=!go
			this.StartButton.btnLabel.text =go? "Pause":"Start";
				
		}
		setInterval(function() {
		    if (go==true && SecondHand){
		        milliseconds+=1000
		        if (milliseconds<900*1000){
		            SecondHand.SecondHand.rotation+=6/60*4;
		        }
				
		    }
		}, 1000);
	}

	// actions tween:
	this.timeline.addTween(cjs.Tween.get(this).call(this.frame_0).wait(1));

	// Layer_1
	this.StartButton = new lib.start_btn();
	this.StartButton.name = "StartButton";
	this.StartButton.parent = this;
	this.StartButton.setTransform(160,356.9,0.828,0.828);
	new cjs.ButtonHelper(this.StartButton, 0, 1, 2, false, new lib.start_btn(), 3);

	this.SecondHand = new lib.SecondHand();
	this.SecondHand.name = "SecondHand";
	this.SecondHand.parent = this;
	this.SecondHand.setTransform(160,154,0.368,0.467,0,0,0,0.1,0.1);

	this.timeline.addTween(cjs.Tween.get({}).to({state:[{t:this.SecondHand},{t:this.StartButton}]}).wait(1));

	// Layer_1
	this.instance = new lib.clockai("synched",0);
	this.instance.parent = this;
	this.instance.setTransform(160.2,159.9,1.631,1.631,0,0,0,98.2,98);

	this.timeline.addTween(cjs.Tween.get(this.instance).wait(1));

}).prototype = p = new cjs.MovieClip();
p.nominalBounds = new cjs.Rectangle(150.2,190.5,341,381.8);
// library properties:
lib.properties = {
	id: 'C2BCD0B9ED83B34B8A51C415248DB295',
	width: 320,
	height: 381,
	fps: 24,
	color: "#FFFFFF",
	opacity: 1.00,
	manifest: [],
	preloads: []
};



// bootstrap callback support:

(lib.Stage = function(canvas) {
	createjs.Stage.call(this, canvas);
}).prototype = p = new createjs.Stage();

p.setAutoPlay = function(autoPlay) {
	this.tickEnabled = autoPlay;
}
p.play = function() { this.tickEnabled = true; this.getChildAt(0).gotoAndPlay(this.getTimelinePosition()) }
p.stop = function(ms) { if(ms) this.seek(ms); this.tickEnabled = false; }
p.seek = function(ms) { this.tickEnabled = true; this.getChildAt(0).gotoAndStop(lib.properties.fps * ms / 1000); }
p.getDuration = function() { return this.getChildAt(0).totalFrames / lib.properties.fps * 1000; }

p.getTimelinePosition = function() { return this.getChildAt(0).currentFrame / lib.properties.fps * 1000; }

an.bootcompsLoaded = an.bootcompsLoaded || [];
if(!an.bootstrapListeners) {
	an.bootstrapListeners=[];
}

an.bootstrapCallback=function(fnCallback) {
	an.bootstrapListeners.push(fnCallback);
	if(an.bootcompsLoaded.length > 0) {
		for(var i=0; i<an.bootcompsLoaded.length; ++i) {
			fnCallback(an.bootcompsLoaded[i]);
		}
	}
};

an.compositions = an.compositions || {};
an.compositions['C2BCD0B9ED83B34B8A51C415248DB295'] = {
	getStage: function() { return exportRoot.getStage(); },
	getLibrary: function() { return lib; },
	getSpriteSheet: function() { return ss; },
	getImages: function() { return img; }
};

an.compositionLoaded = function(id) {
	an.bootcompsLoaded.push(id);
	for(var j=0; j<an.bootstrapListeners.length; j++) {
		an.bootstrapListeners[j](id);
	}
}

an.getComposition = function(id) {
	return an.compositions[id];
}



})(createjs = createjs||{}, AdobeAn = AdobeAn||{});
var createjs, AdobeAn;