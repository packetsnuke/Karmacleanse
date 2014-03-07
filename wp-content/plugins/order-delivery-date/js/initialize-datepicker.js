var disabledDays = eval('['+jQuery('#delivery_date_holidays').val()+']');
var lockoutDays = eval('['+jQuery('#lockout_days').val()+']');

function nd(date)
{
	var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
	var currentdt = m + '-' + d + '-' + y;
	
	var dt = new Date();
	var today = dt.getMonth() + '-' + dt.getDate() + '-' + dt.getFullYear();
	for (i = 0; i < disabledDays.length; i++)
	{
		if( jQuery.inArray((m+1) + '-' + d + '-' + y,disabledDays) != -1 )
		{
			return [false,"","Holiday"];
		}
	}
	
	for (i = 0; i < startDaysDisabled.length; i++)
	{
		if( jQuery.inArray((m+1) + '-' + d + '-' + y,startDaysDisabled) != -1 )
		{
			return [false,"","Cut-off time over"];
		}
	}
	
	for (i = 0; i < lockoutDays.length; i++)
	{
		if( jQuery.inArray((m+1) + '-' + d + '-' + y,lockoutDays) != -1 )
		{
			return [false,"","Booked"];
		}
	}

	return [true];
}

function dwd(date)
{
	var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
	//var day = jQuery.datepicker.formatDate('DD', date);
	var day = 'orddd_weekday_' + date.getDay();
	if (jQuery("#"+day).val() != 'checked')
	{
		return [false];
	}
	return [true];
}

function sdd(date)
{
	var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
	
	// .html() is used when we have zip code groups enabled
	//var deliveryDates = eval('['+jQuery('#delivery_dates').html()+']');
	var deliveryDates = eval('['+jQuery('#delivery_dates').val()+']');
	var dt = new Date();
	var today = dt.getMonth() + '-' + dt.getDate() + '-' + dt.getFullYear();
	for (i = 0; i < deliveryDates.length; i++)
	{
		if( jQuery.inArray((m+1) + '-' + d + '-' + y,deliveryDates) != -1 )
		{
			return [true];
		}
	}
	return [false];
}

function chd(date)
{
	if (jQuery("#specific_delivery_dates").val() == "on")
	{
		var nW = sdd(date);
	}
	else 
	{
		var nW = dwd(date);
	}

	return nW[0] ? nd(date) : nW;

	//return nW;
}

function avd(date)
{
	var delay_days = parseInt(jQuery("#minimumOrderDays").val());
	var noOfDaysToFind = parseInt(jQuery("#number_of_dates").val())
	
	if(isNaN(delay_days))
	{
		delay_days = 0;
	}
	if(isNaN(noOfDaysToFind))
	{
		noOfDaysToFind = 1000;
	}
	
	var minDate = delay_days + 1;
	
	var date = new Date();
	var t_year = date.getFullYear();
	var t_month = date.getMonth()+1;
	var t_day = date.getDate();
	var t_month_days = new Date(t_year, t_month, 0).getDate();
	
	var s_day = new Date( ad( date , delay_days ) );
	start = (s_day.getMonth()+1) + "/" + s_day.getDate() + "/" + s_day.getFullYear();
	var start_month = s_day.getMonth()+1;
	
	var end_date = new Date( ad( s_day , noOfDaysToFind ) );
	end = (end_date.getMonth()+1) + "/" + end_date.getDate() + "/" + end_date.getFullYear();
	
	var loopCounter = gd(start , end , 'days');
	var prev = s_day;
	var new_l_end, is_holiday;
	for(var i=1; i<=loopCounter; i++)
	{
		var l_start = new Date(start);
		var l_end = new Date(end);
		new_l_end = l_end;
		var new_date = new Date(ad(l_start,i));

		var day = "";
		day = 'orddd_weekday_' + new_date.getDay();
		day_check = jQuery("#"+day).val();
		//alert(day_check);
		is_holiday = nd(new_date);
		
		if( day_check != "checked" || is_holiday != 'true' )
		{
			new_l_end = l_end = new Date(ad(l_end,1));
			end = (l_end.getMonth()+1) + "/" + l_end.getDate() + "/" + l_end.getFullYear();
			loopCounter = gd(start , end , 'days');
		}
	}

	var maxMonth = new_l_end.getMonth()+1;
	var number_of_months = parseInt(jQuery("#number_of_months").val());
	if (maxMonth > start_month )
	{
		return {
			minDate: minDate,
	        maxDate: l_end,
			numberOfMonths: number_of_months 
	    };
	}
	else 
	{
		return {
			minDate: minDate,
	        maxDate: l_end
	    };
	}
}

function ad(dateObj, numDays)
{
	return dateObj.setDate(dateObj.getDate() + numDays);
}

function gd(date1, date2, interval)
{
	var second = 1000,
	minute = second * 60,
	hour = minute * 60,
	day = hour * 24,
	week = day * 7;
	date1 = new Date(date1).getTime();
	date2 = (date2 == 'now') ? new Date().getTime() : new Date(date2).getTime();
	var timediff = date2 - date1;
	if (isNaN(timediff)) return NaN;
		switch (interval) {
		case "years":
			return date2.getFullYear() - date1.getFullYear();
		case "months":
			return ((date2.getFullYear() * 12 + date2.getMonth()) - (date1.getFullYear() * 12 + date1.getMonth()));
		case "weeks":
			return Math.floor(timediff / week);
		case "days":
			return Math.floor(timediff / day);
		case "hours":
			return Math.floor(timediff / hour);
		case "minutes":
			return Math.floor(timediff / minute);
		case "seconds":
			return Math.floor(timediff / second);
		default:
			return undefined;
	}
}

function maxdt(date)
{
	var delay_days = parseInt(jQuery("#minimumOrderDays").val());
	var noOfDaysToFind = parseInt(jQuery("#number_of_dates").val())
	
	if(isNaN(delay_days))
	{
		delay_days = 0;
	}
	if(isNaN(noOfDaysToFind))
	{
		noOfDaysToFind = 1000;
	}
	
	var date = new Date();
	var t_year = date.getFullYear();
	var t_month = date.getMonth()+1;
	var t_day = date.getDate();
	var t_month_days = new Date(t_year, t_month, 0).getDate();
	
	var s_day = new Date( ad( date , delay_days ) );
	start = (s_day.getMonth()+1) + "/" + s_day.getDate() + "/" + s_day.getFullYear();
	var start_month = s_day.getMonth()+1;
	
	var end_date = new Date( ad( s_day , noOfDaysToFind ) );
	end = (end_date.getMonth()+1) + "/" + end_date.getDate() + "/" + end_date.getFullYear();
	
	var loopCounter = gd(start , end , 'days');
	var prev = s_day;
	var new_l_end;
	for(var i=1; i<=loopCounter; i++)
	{
		var l_start = new Date(start);
		var l_end = new Date(end);
		new_l_end = l_end;
		var new_date = new Date(ad(l_start,i));

		var day = "";
		day = 'orddd_weekday_' + new_date.getDay();
		day_check = jQuery("#"+day).val();
		
		if(day_check != "checked")
		{
			new_l_end = l_end = new Date(ad(l_end,1));
			end = (l_end.getMonth()+1) + "/" + l_end.getDate() + "/" + l_end.getFullYear();
			loopCounter = gd(start , end , 'days');
		}
	}
	
	var maxMonth = new_l_end.getMonth()+1;
	var number_of_months = parseInt(jQuery("#number_of_months").val());
	if (maxMonth > start_month )
	{
		return {
	        maxDate: l_end,
			numberOfMonths: number_of_months 
	    };
	}
	else 
	{
		return {
	        maxDate: l_end
	    };
	}
}

