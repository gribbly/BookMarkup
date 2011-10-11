<!-- @@mop_helper_scripts|standard mop helper scripts -->

function MopReload() {
  window.location.reload()
}

function MopToggleDevText(){
	$('.mop_DevText').fadeToggle("400");
}

function MopToggleNotes(){
	$('.mop_NoteTextContainer').fadeToggle("slow");
}

function MopToggleVisibility(names) {
	var els = document.getElementsByClassName(names);
	var elsArray = Array.prototype.slice.call(els, 0);
	elsArray.forEach(function(el) {
					 if(el.style.display == 'block') {
						el.style.display = 'none';
					 }
					 else {
						el.style.display = 'block';
					 }
	});
}