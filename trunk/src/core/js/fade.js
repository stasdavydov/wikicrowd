var FadeColor = function (_r, _g, _b) {
	var fadeColor = {
    	r: 0, g: 0, b: 0,
		normalize: function(c) {
			return c >= 255 ? 255 : (c <= 0 ? 0 : c);
    	},
		initialize: function(r, g, b) {
			this.r = this.normalize(r);
			this.g = this.normalize(g);
    		this.b = this.normalize(b);
		},
		getStyle: function() {
			return 'rgb(' + this.r + ', ' + this.g + ', ' + this.b + ')';
    	},
		equal: function(color) {
			return this.r == color.r && this.g == color.g && this.b == color.b;
    	},
		add: function (r, g, b) {
			return new FadeColor(
				this.normalize(this.r + r),
				this.normalize(this.g + g),
    			this.normalize(this.b + b));
		}
    };
    fadeColor.initialize(_r, _g, _b);
    return fadeColor;
};

var FadeEffect = function () {
	return {
    	fadeDuration: 1000,		// 1 sec
    	fadeStep: 5,			// 5 points on every fade step
    	stepDelay: 20,			// delay between fade steps
    	startDelay: 100,		// 0.5 sec
    	className: 'fade',
    	classNameAfterFade: '',
    	startColor: new FadeColor(0, 0xCC, 066),
    	endColor: new FadeColor(0xFF, 0xFF, 0xCC),
    	r: 0, g: 0, b: 0,
    	initialize: function() {},
		cmp: function(a, b) {
			return a > b ? 1 : (a < b ? -1 : 0);
		},

		fadeElementsByClassName: function (el) {
			var me = this;
			if (el.nodeType == 1) { 
				var color = this.startColor;
				var className = el.getAttribute('className');
				if (className == null || className == "")
					className = el.getAttribute('class');
				if (className && className.indexOf(this.className) != -1) {
					if (className != this.className) {
						var idx = className.indexOf(this.className);
						if (idx == 0)
							this.classNameAfterFade = className.substr(this.className.length + 1);
						else
							this.classNameAfterFade = className.substr(0, idx) + ' ' + 
								className.substr(this.className.length);
					}
					setTimeout(function() {
						me.fade(el, color);
					}, this.startDelay);
				}
			}
			if(el.firstChild)
				this.fadeElementsByClassName(el.firstChild);
			if(el.nextSibling)
				this.fadeElementsByClassName(el.nextSibling);
		},

		start: function () {
			this.stepDelay = this.fadeDuration / Math.max(
				Math.abs(this.endColor.r - this.startColor.r), 
				Math.abs(this.endColor.g - this.startColor.g), 
				Math.abs(this.endColor.b - this.startColor.b)) * this.fadeStep;
			this.r = this.cmp(this.endColor.r, this.startColor.r);
			this.g = this.cmp(this.endColor.g, this.startColor.g);
			this.b = this.cmp(this.endColor.b, this.startColor.b);
			this.fadeElementsByClassName(document.documentElement);
		},
		fade: function (el, color) {
			var me = this;
			el.style.background = color.getStyle();
			if (! color.equal(this.endColor)) {
				var newColor = color.add(this.r * this.fadeStep, 
					this.g * this.fadeStep, this.b * this.fadeStep);
				newColor.r = newColor.r > this.endColor.r ? this.endColor.r : newColor.r;
				newColor.g = newColor.g > this.endColor.g ? this.endColor.g : newColor.g;
				newColor.b = newColor.b > this.endColor.b ? this.endColor.b : newColor.b;

				setTimeout(
					function() {
						me.fade(el, newColor);
					}, this.stepDelay);
			} else {
				el.style.background = 'none';
				el.setAttribute('class', this.classNameAfterFade);
				el.setAttribute('className', this.classNameAfterFade);
			}
		}
	};
};

var FadeEffect = new FadeEffect();
