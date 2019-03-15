Array.prototype.removeIf = function(callback) {
	var i = this.length;
	while (i--) {
		if (callback(this[i], i))
			this.splice(i, 1);
	}
};

function c2f(c) { return c*9.0/5.0 + 32.0; }
function f2c(f) { return (f-32.0)*5.0/9.0; }