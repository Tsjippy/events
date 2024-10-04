console.log('Frontend Content Events Script Loaded');
/**
 * Update the day name and weeknumber
 */
function updateDayNameDisplay(){
    let startDate	= new Date(document.querySelector('[name="event[startdate]"]').value);
	let weekDays	= [
		'sunday',
		'monday',
		'tuesday',
		'wednesday',
		'thursday',
		'friday',
		'saturday'
	]

	let weekDay	= weekDays[startDate.getDay()];
	let weekNr	= parseInt(startDate.getDate()/7);

	if(weekDay == undefined){
		weekDay	= '?';
	}

	let nrInWords	= [
		'first',
		'second',
		'third',
		'fourth',
		'fifth'
	]
	let weekWord	= nrInWords[weekNr];

	document.querySelectorAll('.dayname').forEach(el=>el.textContent = weekDay);
	document.querySelectorAll('.weekword').forEach(el=>el.textContent = weekWord);

	// get the last day of the month
	let lastDay	= new Date(startDate.getFullYear(), startDate.getMonth() + 1, 0);

	document.querySelectorAll('.last-day-of-month').forEach(el=>el.classList.add('hidden'));
	document.querySelectorAll('.last-dayname-of-month').forEach(el=>el.classList.add('hidden'));

	// Selected date is the last of the month
	if(startDate.getDate() == lastDay.getDate()){
		// show last day of month radio option
		document.querySelectorAll('.last-day-of-month').forEach(el=>el.classList.remove('hidden'));
	}

	// Selected date is the last weekday of the month (i.e. last friday or such)
	if(startDate.getDate() + 7 > lastDay.getDate()){
		// show last day of month radio option
		document.querySelectorAll('.last-dayname-of-month').forEach(el=>el.classList.remove('hidden'));
	}
}

/**
 * daily, weekly, monthly, yearly selector changed
 */
function repeatTypeChosen(target){
	let parent	= target.closest('.repeat_wrapper');

	//hide all what should be hidden
	parent.querySelectorAll('.hide').forEach(el=>el.classList.replace('hide', 'hidden'));

	updateDayNameDisplay();

	switch(target.value){
		case 'daily':
			//show
			parent.querySelectorAll('.repeatinterval, .days').forEach(el=>el.classList.replace('hidden', 'hide'));
			parent.querySelector('#repeattype').textContent	= 'days';
			break;
		case 'weekly':
			parent.querySelectorAll('.repeatinterval, .weeks').forEach(el=>el.classList.replace('hidden', 'hide')); 
			parent.querySelector('#repeattype').textContent	= 'week(s)';
			break;
		case 'monthly':
			parent.querySelectorAll('.repeatdatetype, .months').forEach(el=>el.classList.replace('hidden', 'hide')); 
			parent.querySelector('#repeattype').textContent			= 'month(s)';
			break;
		case 'yearly':
            parent.querySelectorAll('.repeatdatetype, .years').forEach(el=>el.classList.replace('hidden', 'hide')); 
			parent.querySelector('#repeattype').textContent			= 'year(s)';
			break;
		case 'custom_days':
			parent.querySelectorAll('.custom_dates_selector').forEach(el=>el.classList.replace('hidden', 'hide')); 
			break;
	}
}

function allDayClicked(target){
	let startTime	= target.closest('.event').querySelector('[name="event[starttime]"]');
	let endTime		= target.closest('.event').querySelector('[name="event[endtime]"]');
	let endDate		= target.closest('.event').querySelector('[name="enddate_label"]');

	if(target.checked){
		startTime.classList.add('hidden');
		endTime.classList.add('hidden');
		endDate.classList.add('hidden');

		startTime.value	='00:00';
		endTime.value	= '23:59';
	}else{
		startTime.classList.remove('hidden');
		endTime.classList.remove('hidden');
		endDate.classList.remove('hidden');

		startTime.value	='';
		endTime.value	= '';
	}
}

function startDateChanged(target){
    updateDayNameDisplay();

	let endDate		= target.closest('.event').querySelector('[name="event[enddate]"]');
	let start		= new Date(target.value);
	let end			= new Date(endDate.value);

	if(endDate.value == '' || start>end){
		endDate.value	= target.value;
	}
	let firstWeekday	= new Date(start.getFullYear(), start.getMonth(), 1).getDay();
	let offsetDate		= start.getDate() + firstWeekday - 1;
	let montWeek		= Math.floor(offsetDate / 7);

	document.querySelectorAll('.weeks [name="event[repeat][weeks][]"]')[montWeek].checked	= true;
}

/**
 * Enable or disable even repetition
 *
 * @param   object  target  the html object clicked
 * 
 */
function changeRepeatStatus(target){
	//Change visibility
    document.querySelector('.repeat_wrapper').classList.toggle('hidden');

	// Change repeate value
    let repeated	= target.parentNode.querySelector('[name="event[isrepeated]"]');
    if(repeated.value == 'yes'){
        repeated.value = '';
    }else{
        repeated.value = 'yes';
    }
}

/**
 * Hide and show the relevant options for a given repeat type
 *
 * @param   object  target  the html object clicked
 * 
 */
function repeatStopOptionChosen(target){
    //hide all options
    target.closest('.event').querySelectorAll('.repeat_type_option').forEach(el=>{
        el.querySelector('.repeat_type_option_specifics').classList.add('hidden');	
    });

    //show the selected option
    target.closest('.repeat_type_option').querySelector('.repeat_type_option_specifics').classList.remove('hidden');
}

document.addEventListener("click", event =>{
    let target  = event.target;

    if(target.name == 'enable_event_repeat'){
        changeRepeatStatus(target);
    }else if(target.name == 'event[repeat][stop]'){
        repeatStopOptionChosen(target);
    }else if(target.closest('.repeat_type_option') != null){
		document.querySelectorAll('.repeat_type_option_specifics:not(.hidden)').forEach(el=>el.classList.add('hidden'));
		target.closest('.repeat_type_option').querySelector('.repeat_type_option_specifics').classList.remove('hidden');
	}else if(target.classList.contains('selectall')){
		target.closest('.selector_wrapper').querySelectorAll('input[type="checkbox"]').forEach(el=>el.checked = target.checked);
	}
});

document.addEventListener('change', event=>{
    let target  = event.target;

    if(target.name == 'event[allday]'){
        allDayClicked(target);
    }else if(target.name == 'event[startdate]'){
        startDateChanged(target);
    }else if(target.name == 'event[repeat][type]'){
        repeatTypeChosen(target);
    }
});