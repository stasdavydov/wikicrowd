function getMinutesText(minutes) {
	return minutes + " минут" + 
		(minutes == 1 || (minutes > 20 && minutes % 10 == 1)
			? "у" 
			: ((minutes >= 2 && minutes <= 4) || (minutes > 20 && minutes%10 >= 2 && minutes%10 <= 4)
				? "ы" 
				: (minutes > 20 && minutes % 10 == 1 
					? "а"
					: "")));
}
function getHoursText(hours) {
	return hours + " час" +
		(hours >= 20 && hours%10 == 1
			? "" 
			: ((hours >= 2 && hours <= 4) || (hours >= 20 && hours%10 >= 2 && hours%10 <= 4)
				? "а" 
				: (hours == 0 || hours >= 5
					? "ов"
					: "")));
}
function getDaysText(days) {
	return days + " " + (days == 1 || (days >= 20 && days%10 == 1)
		? "день"
		: ((days >= 2 && days <= 4) || (days >= 20 && days%10 >= 2 && days%10 <= 4)
			? "дня"
			: "дней"));
}
var months = new Array('янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек');
function getTextTimeDifference(ts) {
	var diff = new Date().getTime()/1000 - ts;
	var minutes = diff/60;
	var hours = minutes/60;
	var days = hours/24;
	if (diff <= 60) {
		return "меньше минуты назад";
	} else if (diff > 60 && diff < 3600) {
		return getMinutesText(parseInt(minutes)) + " назад";
	} else if (diff >= 3600 && diff < 3600 * 24)  {
		return getHoursText(parseInt(hours)) + " " + 
			getMinutesText(parseInt(diff%3600/60)) + " назад";
	} else if (diff >= 3600 * 24 && diff < 3600 * 24 * 10) {
		return getDaysText(parseInt(days)) + " " + getHoursText(parseInt(diff/3600%24)) + " " + 
			getMinutesText(parseInt(diff%3600/60)) + " назад";
	} else {
		var date = new Date();
		date.setTime(ts * 1000);
		return date.getDate() + ' ' + 
			months[date.getMonth()] + ' ' +
			date.getFullYear() + ' ' +
			date.getHours() + ':' + date.getMinutes();
	}
}
