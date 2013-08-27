//MOOD-322
function elluminate_warn_grade_session(warnMsg){
	var gradechecked = document.getElementById("id_gradesession").checked; 
	if(!gradechecked){
		alert(warnMsg);
	}
}
