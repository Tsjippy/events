import { addCurrentUserAsHost, removeHost, editTimeSlot, addHostHtml, showTimeslotModal } from './shared.js';

console.log("Mobile-schedule.js loaded");

// Add a new host/ updates an existing entry when the host form is submitted
async function addHost(target, multiple=false){
	var response 	= await FormSubmit.submitForm(target, 'events/add_host');

	if(response){
		if(multiple){
			document.querySelectorAll('.active').forEach(el=>{
				el.classList.remove('active');
				el.outerHTML	= response.html[el.dataset.isodate];
			});

			Main.displayMessage(response.message);

			Main.hideModals();
		}else{
			addHostHtml(response);
		}

		Main.displayMessage(response.message);
	}

    Main.hideModals();
}

document.addEventListener('click', async function(event){
    let target = event.target;

    if(target.closest('.add-me-as-host') != null){
        target  = target.closest('.add-me-as-host');
        target.closest('.day-wrapper-mobile').classList.add('active');
		addCurrentUserAsHost(target.closest('.add-me-as-host'), target.dataset.date);
	}else if(target.closest('.remove-host-mobile') != null){
		event.stopPropagation();
        target      		= target.closest('.remove-host-mobile');
        let sessionWrapper	= target.closest('.session-wrapper-mobile');
		let dayWrapper		= sessionWrapper.closest('.day-wrapper-mobile');
		let isMeal			= sessionWrapper.matches('.meal');

		let result = await removeHost(target, target.dataset.date);

        if(result){
			if(isMeal){
				dayWrapper.outerHTML	= result;
			}else{
            	sessionWrapper.remove();
			}
        }
	}else if (target.name == 'add_host_mobile' ){
		event.stopPropagation();
		addHost(target, true)
	}else if(target.closest('.edit-session-mobile') != null){
        target  = target.closest('.edit-session-mobile');

		let dayWrapper	= target.closest('.day-wrapper-mobile');

        dayWrapper.classList.add('active');

		let wrapper	= target.closest('.session-wrapper-mobile');

		let result = await editTimeSlot(target, target.dataset.isodate, target.dataset.date);

        if(result){
			removeHost(wrapper, target.dataset.date)

            wrapper.remove();
        }
	}else if(target.name == 'add_host' || target.name == 'add_timeslot'){
		event.stopPropagation();
		
		addHost(target);
	}else if(target.name == 'add-session'){
		event.stopPropagation();
		showTimeslotModal(target);
	}else if(target.name == 'add-host'){
		event.stopPropagation();

		Main.showModal(target.closest('.schedules_div').querySelector('.add-host-mobile-wrapper'));
	}
});

document.addEventListener('change', function(event){

	let target = event.target;

	//Direct table actions
	if(target.name == 'starttime' && target.type == 'radio'){
		if(target.value == '12:00'){
			target.closest('form').querySelector('.lunch.select-wrapper').classList.remove('hidden');
			target.closest('form').querySelector('.diner.select-wrapper').classList.add('hidden');
		}else{
			target.closest('form').querySelector('.diner.select-wrapper').classList.remove('hidden');
			target.closest('form').querySelector('.lunch.select-wrapper').classList.add('hidden');
		}
	}else if(target.matches('.schedules_wrapper [name="add_session"] [name="date"]')){
		// Add the active class to the currently selected date
		let form		= target.closest('form');
		let scheduleId	= form.querySelector('[name="schedule_id"').value;

		form.querySelectorAll('.active').forEach(el=>el.classList.remove('active'));

		let dayWrapper	= document.querySelector(`.schedules_div[data-id='${scheduleId}'] .day-wrapper-mobile[data-isodate='${target.value}']`);

		if(dayWrapper != null){
			dayWrapper.classList.add('active');
		}
	}else if(target.matches('.add-host-mobile-wrapper [name="date[]"]')){
		// Add the active class to the currently selected dates
		let form		= target.closest('form');
		let scheduleId	= form.querySelector('[name="schedule_id"').value;

		form.querySelectorAll('.active').forEach(el=>el.classList.remove('active'));

		form.querySelectorAll('[name="date[]"]:checked ').forEach(el=>{
			let dayWrapper	= document.querySelector(`.schedules_div[data-id='${scheduleId}'] .day-wrapper-mobile[data-isodate='${el.value}']`);

			if(dayWrapper != null){
				dayWrapper.classList.add('active');
			}
		});
	}
});