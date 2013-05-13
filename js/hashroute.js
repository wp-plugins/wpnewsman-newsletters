/**
* HashRoute is a javacript jibrary for routing url-hash requests
*
* (c) 2012 Alex Ladyga. http://alexladyga.posterous.com http://twitter.com/neocoder
*  MIT License
*/

(function(exports){

	function regexQuote(str) {
		return (str+'').replace(/([\\\.\+\*\?\[\^\]\$\(\)\{\}=!<>\|\:])/g, function(s, p1){
			return '\\'+p1;
		});
	}

	function tryCall(func, timesNum, delay) {
		delay = delay || 50;
		timesNum = timesNum || 5;
		if ( timesNum <= 0 ) {
			throw new Error('Failed to make deffered call.');
		}
		setTimeout(function() {
			if ( !func() ) {
				tryCall(func, timesNum-1, delay);
			}
		}, delay);
	}

	function createRouter(config) {
		config = config || {};
		var that = {},
			rt = {
				staticRoutes: {

				},
				paramRoutes: {

				}
			}, history = []; // routing table

		function addToHistory(r) {
			history.push(r);
			while ( history.length > 2 ) {
				history.shift();
			}
		}

		function log() {
			if ( config && config.debug && console ) {
				console.log.apply(console, arguments);
			}
		}

		that.back =	function() {
			window.location.hash = history[0];
		};

		function parametrizedRoute(routeStr) {
			return !!routeStr.match(/(:\w+|#)/i);
		}

		function routeStrToRxStr(routeStr) {
			return routeStr.replace(/\#(.*?)\#/gi, '($1)').replace(/\:(\w+)/gi, '([^#/]+)');
		}

		that.on = function(routes, handler /*, handler, ...*/){
			var args = Array.prototype.slice.call(arguments);
			routes = args.shift();
			routes = ( typeof routes === 'string' ) ? [routes] : routes; // converting single route to array to simplify code

			for (var i = 0; i < routes.length; i++) {
				if ( parametrizedRoute(routes[i]) ) {	
					rt.paramRoutes[routeStrToRxStr(routes[i])] = args;
				} else {
					rt.staticRoutes[routes[i]] = args;
				}
			}

		};

		that.redirect = function(newHash) {
			if ( newHash[0] !== '#' ) {
				newHash = '#'+newHash;
			}
			setTimeout(function() {
				window.location.hash = newHash;
			}, 10);
		};

		that.route = function(query) {
			log('[Router]: hashchange '+query);
			var params, handlers, i, r, 
				st = rt.staticRoutes,
				pr = rt.paramRoutes;

			for (r in st) {
				if ( (new RegExp('^'+r+'$')).test(query) ) {
					handlers = st[r];

					for (i = 0; i < handlers.length; i++) {
						handlers[i].apply(that);
					}
					return true;
				}
			}

			for (r in pr) {
				if ( params = (new RegExp('^'+r+'$')).exec(query) ) {
					params.shift();
					handlers = pr[r];
					for (i = 0; i < handlers.length; i++) {
						(handlers[i]).apply(that, params);
					}
					return true;
				}
			}	
			return false;		
		};

		that.hasMatch = function(query) {
			var r, 
				st = rt.staticRoutes,
				pr = rt.paramRoutes;

			for (r in st) {
				if ( (new RegExp('^'+r+'$')).test(query) ) {
					return true;
				}
			}

			for (r in pr) {
				if ( (new RegExp('^'+r+'$')).test(query) ) {
					return true;
				}
			}	
			return false;		
		};		

		if ( 'onhashchange' in window && 'addEventListener' in window ) {
			window.addEventListener('hashchange', function(){
				var r = window.location.hash.substr(1);
				addToHistory(r);
				that.route(r);
			});
		} else {
			var currentHash = window.location.hash;
			var timerEvent = function() {
				if ( window.location.hash !== currentHash ) {
					that.route(window.location.hash.substr(1));
					currentHash = window.location.hash;					
				}
				setTimeout(timerEvent, 50);
			};
			setTimeout(timerEvent, 50);
		}


		that._debugShowRT = function() {
			console.log(JSON.stringify(rt, null, '  '));
		};

		that.init = function(q) {
			log('init');
			if ( window.location.hash.substr(1) === '' ) {
				window.location.hash = '#'+q;
			} else {
				var curHash = window.location.hash.substr(1);
				if ( that.hasMatch(curHash) ) {
					that.route(curHash);
				}
			}
		};

		for ( var routePath in config ) {
			if ( config.hasOwnProperty(routePath) && typeof config[routePath] === 'function' ) {
				that.on(routePath, config[routePath]);
			}
		}

		return that;
	}

	exports.createRouter = createRouter;
})(this);


/*
var router = createRouter({});

var rettype = function(t) {
	var el = document.getElementById('type');
	el.innerHTML = t;
};

router.on('/expences/add', function() { console.log('static 1') }, function(){ console.log('static 2'); rettype('static'); });
router.on('/expences/add/:id', function(val){ rettype('parametrized('+val+')'); });
router.on('/expences/add/num/#\\d+#', function(num){ rettype('parametrized RX(num: '+num+')'); });

router.on(['/test1', '/test2', '/test3'], function(){
	console.log('Routing to /TEST');
});

router.init('/');

//*/



