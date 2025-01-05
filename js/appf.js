function showTime(){
	var date = new Date();
	var h = date.getHours(); // 0 - 23
	var m = date.getMinutes(); // 0 - 59
	var s = date.getSeconds(); // 0 - 59 

	h = (h < 10) ? "0" + h : h;　//10より小さかったら0を前につける
	m = (m < 10) ? "0" + m : m;
	s = (s < 10) ? "0" + s : s;

	var time = h + ":" + m + ":" + s ;
	document.getElementById("ClockDisplay").innerText = time;
	document.getElementById("ClockDisplay").textContent = time;

	setTimeout(showTime, 1000);
}
showTime();