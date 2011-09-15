<!-- @@mop_helper_scripts|standard mop helper scripts -->

function MopReload() {
  window.location.reload()
}

function MopHandleScore(s) {
	var tmp = parseInt(localStorage['mop.score']);
	tmp = tmp + s;
	if(tmp < 0) {
		tmp = 0;
	}
	localStorage['mop.score'] = tmp;
}