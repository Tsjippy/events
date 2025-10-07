import { applyRowSpan, showAddHostModal, addCurrentUserAsHost, addHostHtml, removeHost, showTimeslotModal, checkConfirmation, editTimeSlot } from './shared.js';

console.log("Desktop-schedule.js loaded");

/**
 *
 * Submits the new schedule form via rest API and adds the new schedule to the screen.
 *
 * @listens to button click if button has name add_schedule
 *
 * @param {Object} target     The clicked button.
 *
 * @return nothing
*/ 
async function addSchedule(target){
	var response = await FormSubmit.submitForm(target, 'events/add_schedule');

	Main.hideModals();

	if(response){
		target.closest('.schedules-wrapper').outerHTML=response.html;
		
		Main.displayMessage(response.message);

		addSelectable();
	}
}

function ShowPublishScheduleModal(target){
	var modal			= target.closest('.schedules-div').querySelector('.publish-schedule');
	modal.querySelector('[name="schedule-id"]').value = target.dataset["schedule_id"];
	if(target.dataset.target != null){
		modal.querySelector('[name="schedule-target"]').value = target.dataset.target;
		modal.querySelector('[name="publish-schedule"]').click();
		Main.showLoader(target,true);
	}else{
		Main.showModal(modal);
	}
}

async function publishSchedule(target){
	var response	= await FormSubmit.submitForm(target, 'events/publish_schedule');

	if(response){
		document.querySelectorAll('.schedule-actions .loader-wrapper').forEach(el=>el.classList.add('hidden'));
		
		Main.hideModals();

		Main.displayMessage(response);

		document.querySelectorAll('.schedule.publish.warning').forEach(el=>el.remove());
		
	}
}

// Removes an existing schedule
async function removeSchedule(target){
	var scheduleId		= target.dataset["schedule_id"];
	var text 			= "Are you sure you want to remove this schedule";
	var formData 		= new FormData();
	formData.append('schedule_id', scheduleId);

	var confirmed		= await checkConfirmation(text, target.closest('.schedules-div'));
	if(confirmed){
		var response	= await FormSubmit.fetchRestApi('events/remove_schedule', formData);

		if(response){
			Main.displayMessage(response);

			document.querySelector('.schedules-wrapper .loader-wrapper:not(.hidden)').remove();
		}
	}
}

function removeRowSpan(cell){
	cell.removeAttribute('rowspan');
	let row			= cell.closest('tr').nextElementSibling;
	if(row != null){
		cell			= row.cells[cell.cellIndex];
		while(cell.matches('.hidden')){
			cell.classList.remove('hidden');
			row				= row.nextElementSibling;
			cell			= row.cells[cell.cellIndex];
		}
	}
}

// Add a new host/ updates an existing entry when the host form is submitted
async function addHost(target){
	let form		= target.closest('form');
	let sessionId	= form.querySelector('[name="session-id"]').value;
	let date		= form.querySelector('[name="date"]').value;
	let startTime	= form.querySelector('[name="starttime"]').value;
	let endTime		= form.querySelector('[name="endtime"]').value;
	let newCell, table, newRow, newCol, cell, oldStartTime, oldDate, oldEndTime;

	cell			= document.querySelector('td.active');
	if(cell == null){
		return;
	}

	Main.showLoader(cell.firstChild);

	var response 	= await FormSubmit.submitForm(target, 'events/add_host');

	if(response){
		table			= cell.closest('table');
		newCell	= cell;
		newRow	= cell.closest('tr');
		newCol	= cell.cellIndex;

		if(sessionId != ''){
			oldStartTime	= cell.dataset.starttime;
			oldEndTime		= cell.dataset.endtime;
			oldDate			= table.rows[0].cells[1].dataset.isodate;
			
			// Adjust the table if date or times changed
			if(oldDate != date || oldStartTime != startTime || oldEndTime != endTime){
				removeRowSpan(cell);

				if(oldDate != date || oldStartTime != startTime){
					cell.innerHTML	= 'Available';

					// find the new cell
					newRow	= table.querySelector(`tr[data-starttime="${startTime}"]`);
					newCol	= table.rows[0].querySelector(`[data-isodate="${date}"]`).cellIndex;
					newCell	= newRow.cells[newCol];

					// make old cell a normall cell
					cell.classList.remove('ui-selected', 'active', 'selected');

					// make the new cell the active cell
					newCell.classList.add('ui-selected', 'active', 'selected');
				}
			}
		}

		addHostHtml(response);

		// Get the new inserted cell
		cell	= newRow.cells[newCol];

		applyRowSpan(cell, cell.rowSpan);

        addSelectable();
	}
}

// Show the modal to add a recipe keyword
function showRecipeModal(target){
	target.classList.add('active');

	var table			= target.closest('table');
	var heading			= table.tHead.rows[0];
	var cell			= target.closest('td');
	var date			= heading.cells[cell.cellIndex].dataset.isodate;
	var startTime		= target.closest('tr').dataset.starttime;

	//Fill the modal with the values of the clickes schedule
	var recipeModal 	= document.querySelector('[name="recipe-keyword-modal"]');

	FormSubmit.formReset(recipeModal.querySelector('form'));

	recipeModal.querySelector('[name="schedule-id"]').value 	= table.dataset["id"];
	recipeModal.querySelector('[name="date"]').value 			= date;
	recipeModal.querySelector('[name="starttime"]').value 		= startTime;

	if(target.textContent != 'Enter recipe keyword'){
		recipeModal.querySelector('[name="recipe-keyword"]').value	= target.textContent;
		document.querySelector('[add-recipe-keyword"]').textContent	= 'Update recipe keywords';
	}
	
	Main.showModal(recipeModal);
}

//submit the recipe form
async function submitRecipe(target){
	var response = await FormSubmit.submitForm(target, 'events/add_menu');

	document.querySelector('.active').textContent	= target.closest('form').querySelector('[name="recipe-keyword"]').value;

	document.querySelector('.active').classList.remove('active');

	Main.hideModals();

	Main.displayMessage(response);
}

function showEditScheduleModal(target){
	let modal		= document.getElementById('edit-schedule_modal');
	let wrapper		= target.closest('.schedules-div');
	let table		= wrapper.querySelector('table.schedule');

	let scheduleId	= wrapper.dataset.id;

	modal.querySelector(`[name="schedule-id"]`).value		= scheduleId;
	modal.querySelector(`[name="target-id"]`).value			= table.dataset.target_id;
	modal.querySelector(`[name="target-name"]`).value		= table.dataset.target;
	modal.querySelector(`[name="schedule-info"]`).value		= wrapper.querySelector('.table-title.sub-title').textContent;
	modal.querySelector(`[name="startdate"]`).value			= table.tHead.querySelector('tr').cells[1].dataset.isodate;
	modal.querySelector(`[name="enddate"]`).value			= table.tHead.querySelector('tr').cells[table.tHead.querySelector('tr').cells.length-1].dataset.isodate;
	modal.querySelector(`[name="timeslotsize"]`).value		= wrapper.dataset.slotsize;
	if(wrapper.dataset.fixedslotsize == 1){
		modal.querySelector(`[name='fixedtimeslotsize'][value='yes']`).checked = true;
	}else{
		modal.querySelector(`[name='fixedtimeslotsize'][value='no']`).checked = true;
	}
	modal.querySelector(`[name="subject"]`).value			= wrapper.dataset.subject;
	modal.querySelector(`[name="hidenames"]`).checked		= wrapper.dataset.hidenames;
	modal.querySelector(`[name="skiplunch"]`).checked		= parseInt(table.dataset.skiplunch);
	modal.querySelector(`[name="skipdiner"]`).checked		= parseInt(table.dataset.skipdiner);
	modal.querySelector(`[name="skiporientation"]`).checked	= table.rows.length<3;

	let adminRoles	= JSON.parse(table.dataset.adminroles);
	modal.querySelectorAll(`[name="admin-roles[]"] option`).forEach(option => option.selected = adminRoles.includes(option.value));
	modal.querySelectorAll(`[name="admin-roles[]"]`).forEach(select => select.dispatchEvent(new Event('change')));

	let viewRoles	= JSON.parse(table.dataset.viewroles);

	modal.querySelectorAll(`[name="view-roles[]"] option`).forEach(option => option.selected = viewRoles.includes(option.value));
	modal.querySelectorAll(`[name="view-roles[]"]`).forEach(select => select.dispatchEvent(new Event('change')));

	Main.showModal(modal);
}

async function checkIfValidSelection(target, selected, e){
	//we have selected something and there are no open modals
	let openModals	= document.querySelectorAll('.modal:not(.hidden)');
	if((selected.length > 0 || target.classList.contains('orientation')) && (openModals == null || openModals.length == 0) ){

		let firstCell;
		let lastCell;
		if(selected.length > 0){
			//check if selection is valid
			let columnNr	= selected[0].node.cellIndex;
			firstCell		= selected[0].node;
            lastCell		= selected[selected.length-1].node;

			for (const selection of selected){
				if(columnNr != selection.node.cellIndex){
					let options = {
						icon: 'error',
						title: "You can not select times on multiple days!",
						confirmButtonColor: "#bd2919",
					};

					if(document.fullscreenElement != null){
						options['target']	= document.fullscreenElement;
					}

					Swal.fire(options);

					e.target.closest('.sim-table')._selectable.clear();
					return;
				}
			}
		}else{
			firstCell	= target;
			lastCell	= target;
		}
		
		firstCell.dataset.oldvalue	= firstCell.innerHTML;

		//We are dealing with a cell with a value
		if(firstCell.classList.contains('selected')){
            let date    = firstCell.closest('table').rows[0].cells[firstCell.cellIndex].dataset.isodate;
            let dateStr = firstCell.closest('table').rows[0].cells[firstCell.cellIndex].dataset.date;

            firstCell.classList.add('active');
			let result  = await editTimeSlot(firstCell, date, dateStr);
            if(result){
                deleteHost(firstCell);
            }
		}else{
            let rowCount		= document.querySelectorAll('.ui-selected').length;
            applyRowSpan(firstCell, rowCount);

            // Only show loader when the cell is empty
            if(!firstCell.matches('.selected')){
                Main.showLoader(firstCell.firstChild);
			}

            firstCell.classList.add('active');

			showTimeslotModal(selected);
		}
	}
}

async function deleteHost(target){
    let cell    = target.closest('td');
    let classes = cell.classList.value;
    let index   = cell.cellIndex;
    let row     = cell.closest('tr');
    let dateStr = cell.closest('table').rows[0].cells[cell.cellIndex].dataset.date;

    let result  = await removeHost(cell, dateStr);

    if(result){
        cell.outerHTML   		= result;

        cell                    = row.cells[index];
		removeRowSpan(cell);
    }
}

//Do something with selected items
async function afterSelect(e, selected, _unselected){
	let target;
	if(selected[0] != undefined){
		target 	= selected[0].node;
	}else{
		target	= e.target;
	}

	//remove a meal host
	if(target.matches('.meal.selected.own, .meal.selected.admin')){
        deleteHost(target);
	//Add a host
	}else if(target.matches('.meal.admin')){
        let table											= target.closest('table');
        let cell											= target.closest('td');
		cell.classList.add('active');
        let date											= table.rows[0].cells[cell.cellIndex].dataset.isodate;
        let startTime										= target.closest('tr').dataset.starttime;
        
		showAddHostModal(target, date, startTime);
	//orientation slot
	}else if(target.matches('.add-current:not(.selected, .admin)')){
        let table		= target.closest('table');
		let cell		= target.closest('td');
		let dateStr		= table.rows[0].cells[cell.cellIndex].dataset.date;

        target.classList.add('active');
		addCurrentUserAsHost(target, dateStr);
	}else{
		checkIfValidSelection(target, selected, e); 
	}
}

//hide empty rows on mobile
function hideRows() {
	//only run for smaller screens
	if (window.innerWidth > 800) return false;
	
	//Loop over all tables
	document.querySelectorAll('.sim-table tr').forEach(function(tr){
		var emptyRow = true;
		
		//DO not hide lunch or dinner rows
		if(tr.dataset.starttime != 'dinner' && tr.dataset.starttime != 'lunch'){
			//loop over all table cells to see if there is a cell with content
			tr.querySelectorAll('td').forEach(function(cell,index){
				if(index>0 && cell.textContent != 'Available' && cell.textContent != ' '){
					emptyRow = false;
				}
			});
			
			//If non of the cells have content
			if(emptyRow ){
				//hide the row
				tr.classList.add('hidden');
			}
		}
	});
}

function addSelectable(){
	//loop over all the schedule tables
	document.querySelectorAll('.sim-table.schedule').forEach(function(table){
		//Add selectable on non-mobile devices
		if(!Main.isMobileDevice() || table.rows.length < 7){
			if(table._selectable != undefined){
				table._selectable.destroy();
			}

			let options		= {
				filter:		".orientation.available",
				appendTo:	table.querySelector("tbody"),
				lasso: {
					 border: "none",
					 backgroundColor: "none"
				 }
			}

			// only allow 1 timeslot if we have fixed timeslots
			if(table.closest('.schedules-div.table-wrapper').dataset.fixedslotsize == '1' ){
				options.maxSelectable	= 1;
			}

			//Load selectable and attach it to the table
			table._selectable = new Selectable(options);
			
			//Run the function afterSelect when selection is final
			table._selectable.on('end', afterSelect);
		}
	});

	hideRows();
}

function modalclosed(ev){
	document.querySelectorAll('td.active').forEach(cell => {
		cell.innerHTML = cell.dataset.oldvalue;
		cell.classList.remove('active', 'ui-selected');
	});
}

document.addEventListener("DOMContentLoaded",function() {
	
	addSelectable();

	document.querySelector('[name="add-session"]').addEventListener('modalclosed', modalclosed);
});

document.addEventListener('click', function(event){
	let target = event.target;

	if(target.name == 'add_schedule'){
		event.stopPropagation();

		addSchedule(target);
	}else if(target.name == 'publish-schedule'){
		event.stopPropagation();

		publishSchedule(target);
	}else if(target.name == 'add-recipe-keyword'){
		event.stopPropagation();

		submitRecipe(target);
	}else if(target.matches('.modal .close')  && target.closest('.modal').attributes.name.value == 'add-session'){
		removeRowSpan(document.querySelector('td.active'));
	}else if(target.classList.contains('keyword')){
		showRecipeModal(target);
	}else if(target.classList.contains('publish')){
		event.stopPropagation();

		ShowPublishScheduleModal(target);
	}else if(target.classList.contains('remove-schedule')){
		event.stopPropagation();

		removeSchedule(target);
	}else if(target.matches('.edit-schedule')){
		event.stopPropagation();

		showEditScheduleModal(target);
	}else if(target.name == 'add-host' || target.name == 'add-timeslot'){
		event.stopPropagation();
		
		addHost(target);
	}
});

document.addEventListener('change', function(event){

	let target = event.target;

	//Direct table actions
	if(target.name=='target-name'){
		var userId = target.list.querySelector(`[value='${target.value}' i]`).dataset.value;

		target.closest('form').querySelector('[name="target-id"]').value	= userId;
	}else if(target.id == 'host'){
		let host	= target.list.querySelector(`[value="${target.value}"]`);
		let userId	= '';
		if(host != null){
			userId	= host.dataset.user_id;
		}

		target.closest('form').querySelector('[name="host-id"]').value	= userId;
	}

	/* if(target.matches('[name="add-session"] .time')){
		// get the active cell
		let cell		= document.querySelector('td.active');

		if(cell == null){
			return;
		}
		let table		= cell.closest('table');

		// validate the time
		if(
			(target.name == 'starttime' && table.querySelector(`tr[data-starttime="${target.value}"]`) == null) ||
			(target.name == 'endtime' && table.querySelector(`tr[data-endtime="${target.value}"]`) == null) 
		){
			return;
		}

		let form		= target.closest('form');
		let d			= new Date();
		let starttime	= form.querySelector('[name="starttime"]').value.split(':');
		starttime		= new Date(d.getFullYear(), 0, 1, starttime[0], starttime[1]);
		let endtime		= form.querySelector('[name="endtime"]').value.split(':');
		endtime			= new Date(d.getFullYear(), 0, 1, endtime[0], endtime[1]);
		// calculate the quarters between end and starttime
		let rowSpan		= (endtime-starttime)/1000/60/15

		// starttime changed
		if(starttime != target.value){
			// adjust the current cell
			cell.removeAttribute('rowspan');
			cell.classList.remove('ui-selected', 'active');

			// find the new cell
			let newRow	= cell.closest('table').querySelector('tr[data-starttime="'+form.querySelector('[name="starttime"]').value+'"]');
			let newCell	= newRow.cells[cell.cellIndex];

			newCell.innerHTML	= cell.innerHTML;
			newCell.classList.add('ui-selected', 'active');
			applyRowSpan(newCell, rowSpan);
		// endtime changed
		}else{
			applyRowSpan(cell, rowSpan);
		}
	} */
});