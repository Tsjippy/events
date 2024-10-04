//shows the modal to select a user as host
export function showAddHostModal(target, date='', startTime=''){
	target.classList.add('active');
	let modal											= document.querySelector('[name="add_host"]');

	// Clear
	modal.querySelectorAll('input:not([type="hidden"], [type="radio"])').forEach(el=>el.value='');

    modal.querySelector('[name="date"]').value			= date;
    modal.querySelector('[name="starttime"]').value		= startTime;

	// Add new values
	modal.querySelector('[name="schedule_id"]').value	= target.closest('.schedules_div').dataset.id;

	modal.classList.remove('hidden');
}

// Add current user as host
export async function addCurrentUserAsHost(target, dateStr){
    let scheduleOwner	= target.closest('.schedules_div').dataset.target;   
	let text 			= `Please confirm you want to be the host for ${scheduleOwner} on ${dateStr}`;
	let confirmed		= await checkConfirmation(text, target.firstChild);

	if(confirmed){

		let formData		= loadHostFormdata(target);

		if(target.closest('table') == null){
			Main.showLoader(target);
		}else{
			target.innerHTML = '<span></span>';
			Main.showLoader(target.querySelector('span'));
		}

		let response	= await FormSubmit.fetchRestApi('events/add_host', formData);

		if(response){
			addHostHtml(response);
		}
	}
}

// Show the new host in the schedule
export function addHostHtml(response){
	let target			= document.querySelector('.active');

	if(target != null){
		target.classList.remove('active');
		target.outerHTML	= response.html;
	}

	Main.displayMessage(response.message);

	Main.hideModals();
}

// Remove a host
export async function removeHost(target, dateStr){
	let text, confirmed;

    let scheduleOwner	= target.closest('.schedules_div').dataset.target;

	if(target.classList.contains('orientation')){
		text 	= `Are you sure you want to remove this orientation session`;
	}else if(target.classList.contains('admin')){
		text 	= `Please confirm host removal for ${scheduleOwner} on ${dateStr}`;
	}else{
		text 	= `Please confirm you do not want to be the host for ${scheduleOwner} on ${dateStr} anymore`;
	}

	confirmed	= await checkConfirmation(text, target.firstChild);

	if(confirmed){
		
		let formData		= new FormData();
		formData.append('session_id', target.dataset.session_id);
		Main.showLoader(target.firstChild);

		var response = await FormSubmit.fetchRestApi('events/remove_host', formData);

		Main.displayMessage(response.message);

		return response.html;
	}

    return confirmed;
}

export function showTimeslotModal(selected=''){
	let reminders, firstCell, lastCell, date, startTime, endTime, hostId, oldTime, subject, location, hostName, eventId, sessionId, atendees, li, html, option;
	let modal 		= document.querySelector('[name="add_session"]');
	let ul			= modal.querySelector('ul.listselectionlist');

	// Clear
	modal.querySelectorAll('input:not([type="hidden"], [type="checkbox"], [type="radio"])').forEach(el=>el.value='');
	if(ul != null){
		ul.innerHTML	= '';
	}

	if(selected[0] == undefined){
		firstCell	= selected;
		startTime	= firstCell.dataset.starttime;
		endTime		= firstCell.dataset.endtime;
	}else{
		firstCell		= selected[0].node;
		lastCell		= selected[selected.length-1].node;
		let rowCount	= document.querySelectorAll('.ui-selected').length;
		applyRowSpan(firstCell, rowCount);

		// Only show loader when the cell is empty
		if(!firstCell.matches('.selected')){
			Main.showLoader(firstCell.firstChild);
		}
	}
	modal.querySelector('[name="schedule_id"]').value		= firstCell.closest('.schedules_div').dataset.id;
	
	let table	= firstCell.closest('table');
	startTime	= firstCell.dataset.starttime;
	endTime		= firstCell.dataset.endtime;
	date	 	= firstCell.dataset.isodate;
	if(firstCell.closest('tr') != null){
		startTime	= firstCell.closest('tr').dataset.starttime;
		endTime		= lastCell.closest('tr').dataset.endtime;
		date		= table.rows[0].cells[firstCell.cellIndex].dataset.isodate;
	}

	hostId			= firstCell.dataset.host_id;
	//oldTime			= firstCell.dataset.old_time;
	subject			= firstCell.dataset.subject;
	location		= firstCell.dataset.location;
	hostName		= firstCell.dataset.host;
	sessionId		= firstCell.dataset.session_id;
	if(firstCell.dataset.reminders != undefined){
		reminders		= JSON.parse(firstCell.dataset.reminders);

		modal.querySelectorAll('[name="reminders[]"]').forEach(el => {
			if(reminders.includes(el.value)){
				el.checked	= true;
			}else{
				el.checked	= false;
			}
		});
	}

	if(firstCell.dataset.atendees != undefined){
		atendees		= JSON.parse(firstCell.dataset.atendees);
	}
	
	if(firstCell.closest('.day-wrapper-mobile') != null){
		firstCell.closest('.day-wrapper-mobile').classList.add('active');
	}else{
		firstCell.classList.add('active');
	}

	//Fill the modal values
	modal.querySelector('[name="date"]').value				= date;
	modal.querySelector('[name="starttime"]').value			= startTime;
	modal.querySelector('[name="endtime"]').value			= endTime;

	if(firstCell.closest('.schedules_div.table-wrapper').dataset.fixedslotsize == '1' ){
		modal.querySelector('[name="endtime"]').closest('label').querySelector('h4').textContent = 'End time';
		modal.querySelector('[name="endtime"]').disabled	= true;
	}else{
		modal.querySelector('[name="endtime"]').disabled	= false;
		modal.querySelector('[name="endtime"]').closest('label').querySelector('h4').textContent = 'Select an end time:'
	}
	
	if(firstCell.closest('.schedules_div.table-wrapper').dataset.subject == '' || firstCell.closest('.schedules_div.table-wrapper').dataset.subject == undefined){
		modal.querySelector('[name="subject"]').value		= '';
		modal.querySelector('[name="endtime"]').disabled	= false;
	}else{
		modal.querySelector('[name="subject"]').value		= firstCell.closest('.schedules_div.table-wrapper').dataset.subject;
		modal.querySelector('[name="endtime"]').disabled	= true;
	}

	modal.querySelector('[name="add_timeslot"]').classList.remove('add_schedule_row');
	modal.querySelector('[name="add_timeslot"]').classList.add('update_schedule');

	if(sessionId != undefined){
		modal.querySelector('[name="session-id"]').value	= sessionId;
	}
	if(hostId	!= undefined){
		modal.querySelector('[name="host_id"]').value		= hostId;

	}
	if(subject	!= undefined){
		modal.querySelector('[name="subject"]').value		= subject;
	}
	if(location	!= undefined){
		modal.querySelector('[name="location"]').value		= location;
	}
	if(hostName	!= undefined){
		modal.querySelector('#host').value					= hostName;
	}
	if(atendees != undefined){
		atendees.forEach(atendee => {
			li	 		= document.createElement('li');
			li.classList.add('listselection');

			html	= `<button type="button" class="small remove-list-selection"><span class='remove-list-selection'>×</span></button>`;

			if(typeof(atendee) === 'object'){
				html   += `<input type='hidden' name='others[]' value='${atendee.id}'>`;
				html   += `<span>${atendee.name}</span>`;
			}else{
				html   += `<span>`;
					html   += `<input type='text' name='others[]' value='${atendee}' readonly=readonly style='width:${atendee.length}ch'>`;
				html   += `</span>`;
			}

			li.innerHTML	= html;

			ul.appendChild(li);
		});
	}
	
	Main.showModal(modal);
}

export async function editTimeSlot(target, date, dateStr){
	let options	= {
		title: 'What do you want to do?',
		text: "Do you want to edit or remove this timeslot?",
		showDenyButton: true,
		showCancelButton: true,
		confirmButtonText: 'Edit timeslot',
		denyButtonText: 'Remove timeslot',
	}

	if(document.fullscreenElement != null){
		options['target']	= document.fullscreenElement;
	}

	var answer = await Swal.fire(options);

	//swap and/or
	if (answer.isConfirmed) {
		// Show modal and prefill the default fields
		showTimeslotModal(target, date, target.dataset.starttime, target.dataset.endtime);
		
		//change button text
		var modal 			= document.querySelector('[name="add_session"]');
		modal.querySelector('[name="add_timeslot"]').textContent	= 'Update time slot';
	//add new rule after this one
	}else if (answer.isDenied) {
		return answer.isDenied;
	}
}

function loadHostFormdata(target){
    let scheduleId		= target.closest('.schedules_div').dataset.id;
	let table			= target.closest('table');

	let heading, cell, date, startTime, host;

	if(table == null){
		startTime		= target.dataset.starttime;
		host			= target.dataset.host_id;
		date			= target.dataset.isodate
	}else{
		heading			= table.tHead.rows[0];
		cell			= target.closest('td');
		startTime		= target.closest('tr').dataset.starttime;
		host			= cell.dataset.host;
		date			= heading.cells[cell.cellIndex].dataset.isodate;
	}

	var formData		= new FormData();
	formData.append('date', date);
	if(host != null){
		formData.append('host_id', host);
	}else{
		formData.append('host_id', sim.userId);
	}
	
	formData.append('starttime', startTime);
	formData.append('schedule_id', scheduleId);

	formData.append('subject', target.closest('.schedules_div.table-wrapper').dataset.subject)

	return formData;
}

export async function checkConfirmation(text, target){
	let options = {
		title: 'Are you sure?',
		text: text+"?",
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: "#bd2919",
		cancelButtonColor: '#d33',
		confirmButtonText: 'Yes'
	};

	if(document.fullscreenElement != null){
		options['target']	= document.fullscreenElement;
	}

	var result = await Swal.fire(options);
	
	if (result.isConfirmed) {
		document.querySelectorAll('.modal:not(.hidden)').forEach(modal=>modal.classList.add('hidden'));
		//display loading gif
		Main.showLoader(target.firstChild);
		
		return true;
	}

	return false;
}


export function applyRowSpan(target, count){
	if(count > 1){
		var row		= target.closest('tr');
		var index	= target.cellIndex;
		//Loop over the next cells to add the hidden attribute
		for (var i = 1; i < count; i++) {
			row = row.nextElementSibling;
			row.cells[index].classList.add('hidden');
		}
		target.rowSpan	= count;
	}
}