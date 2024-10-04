async function requestMonth(target, month, year){
    url.searchParams.set('month', month);
    url.searchParams.set('yr', year);
    window.history.pushState({}, '', url);

    //hide all
    document.querySelectorAll('#monthview .events-wrap:not(hidden)').forEach(el=>el.classList.add('hidden'));

    let calendarPage   = document.querySelector(`.events-wrap[data-date="${year}-${month}"]`);
    if(calendarPage == null){
        target.closest('.calendar-wrap').insertAdjacentHTML('beforeEnd', `<img class="loader" src="${sim.loadingGif}" style="margin-left: auto;margin-right: auto;display: block;">`);
        
        let formData = new FormData();
        formData.append('month', month);
        formData.append('year', year);

        let response    = await FormSubmit.fetchRestApi('events/get_month_html', formData);
        
        if(response) {
            target.closest('.calendar-wrap').querySelector('.loader').remove();
            document.querySelector('#monthview').insertAdjacentHTML('beforeEnd', response);
        }
    }else{
        calendarPage.classList.remove('hidden');
    }

    document.querySelector('div.month_selector .current').textContent = document.querySelector('select.month_selector').options[month-1].text;
    document.querySelector('div.year_selector .current').textContent = year;
}

async function requestWeek(target, wknr, year){
    url.searchParams.set('yr', year);
    url.searchParams.set('week', wknr);
    window.history.pushState({}, '', url);

    //hide all
    document.querySelectorAll('#weekview .events-wrap:not(hidden)').forEach(el=>el.classList.add('hidden'));

    let calendarPage   = document.querySelector(`.events-wrap[data-weeknr="${wknr}"]`);
    if(calendarPage == null){
        target.closest('.calendar-wrap').insertAdjacentHTML(
            'beforeEnd',
            `<img class="loader" src="${sim.loadingGif}" style="margin-left: auto;margin-right: auto;display: block;">`
        );
        
        let formData = new FormData();
        formData.append('wknr',wknr);
        formData.append('year',year);

        let response    = await FormSubmit.fetchRestApi('events/get_week_html', formData);
        
        if(response) {
            target.closest('.calendar-wrap').querySelector('.loader').remove();
            document.querySelector('#weekview').insertAdjacentHTML('beforeEnd',response);
        }
    }else{
        calendarPage.classList.remove('hidden');
    }

    document.querySelector('div.week_selector .current').textContent = wknr;
    document.querySelector('div.year_selector .current').textContent = year;
}

async function requestExpandList(offset, month='', year=''){
    //remove any existing element when requesting specific date
    if(month != '' || year != ''){
        document.querySelectorAll('#listview article').forEach(el=>el.remove());

        url.searchParams.set('yr', year);
        url.searchParams.set('month', month);
        window.history.pushState({}, '', url);
    }

    document.getElementById('listview').insertAdjacentHTML('beforeEnd','<img class="loader" src="'+sim.loadingGif+'" style="margin-left: auto;margin-right: auto;display: block;">');
    
    let formData = new FormData();
    formData.append('offset',offset);
    formData.append('month',month);
    formData.append('year',year);
    
    let response    = await FormSubmit.fetchRestApi('events/get_list_html', formData);
        
    if(response) {
        document.querySelector('#listview').querySelector('.loader').remove();
        document.querySelector('#listview').insertAdjacentHTML('beforeEnd',response);
    }
}                                                    
                                                                           
function handleTouchStart(evt) {
    const firstTouch = evt.touches[0];                                      
    xDown = firstTouch.clientX;                                      
    yDown = firstTouch.clientY;                                      
}                                              
                                                                           
function handleTouchMove(evt) {
    if ( ! xDown || ! yDown ) {
        return;
    }

    let target;

    let xUp = evt.touches[0].clientX;                                    
    let yUp = evt.touches[0].clientY;

    let xDiff = xDown - xUp;
    let yDiff = yDown - yUp;
                                                                        
    if ( Math.abs( xDiff ) > Math.abs( yDiff ) ) {
        let selected_view   = document.querySelector('.viewselector.selected').dataset.type;

        if ( xDiff > 0 ) {
            /* right swipe */
            target  = evt.target.closest('.events-wrap').querySelector('.next a');
        } else {
            /* left swipe */
            target  = evt.target.closest('.events-wrap').querySelector('.prev a');
        }
        let month = target.dataset.month;
        let week  = target.dataset.weeknr;
        let year  = target.dataset.year;
        
        if(selected_view == 'weekview'){
            requestWeek(evt.target, week, year);
        }else if(selected_view == 'monthview'){
            requestMonth(target, month, year);
        }
    }
    /* reset values */
    xDown = null;
    yDown = null;                                             
}

const url = new URL(window.location);
var xDown = null;                                                        
var yDown = null;

console.log("Events.js loaded");

function prevNext(target){
    let requestedMonth = target.dataset.month;
    let requestedWeek  = target.dataset.weeknr;
    let requestedYear  = target.dataset.year;

    if(requestedMonth != null){
        requestMonth(target, requestedMonth, requestedYear);
    }else if(requestedWeek != null){
        requestWeek(target, requestedWeek, requestedYear);
    }
}

function calendarDayClicked(target){
    let date    = target.dataset.date;

    //hide events of all days
    document.querySelectorAll('.event-details-wrapper:not(.hidden)').forEach(el=>el.classList.add('hidden'));
    
    //show the events of this day
    let details = target.closest('.events-wrap').querySelector(`.event-details-wrapper[data-date="${date}"]`);
    details.classList.remove('hidden');

    //unselect previous slected date
    document.querySelectorAll('.calendar-day.selected').forEach(el=>el.classList.remove('selected'));

    //make this date selected
    target.classList.add('selected');

    // scroll to the event
    details.scrollIntoView({behavior:'auto', block:'start', inline:'center'});
}

function hourClicked(target){
    let date        = target.dataset.date;
    let startTime   = target.dataset.starttime;

    //hide all other events
    document.querySelectorAll('.event-details-wrapper:not(.hidden)').forEach(el=>el.classList.add('hidden'));
    
    //show the event
    let eventDetail = target.closest('.events-wrap').querySelector(`.event-details-wrapper[data-date="${date}"][data-starttime="${startTime}"]`);
    if(eventDetail == null){
        target.closest('.events-wrap').querySelector('.event-details-wrapper[data-date="empty"]').classList.remove('hidden');
    }else{
        eventDetail.classList.remove('hidden');

        //unselect previous selected date
        document.querySelectorAll('.calendar-hour.selected').forEach(el=>el.classList.remove('selected'));
        //make this date selected
        target.classList.add('selected');

        if(Main.isMobileDevice()){
            window.scrollTo(0, eventDetail.offsetTop);
            
		    console.log('scrolling')
        }else{
            //scroll the detail into view
            eventDetail.scrollIntoView({behavior:'auto', block:'start', inline:'center'});
            
            // make sure the vertical scroll is ok too
            window.scrollTo(0, eventDetail.offsetHeight);
            
		console.log('scrolling')
        }
    }

}

function viewChanged(target){
    let parent  = target.closest('.search-form');
    if(target.dataset.type  == 'weekview' || target.dataset.type  == 'monthview'){
        parent.querySelector('div.week_selector').classList.toggle('hidden');
        parent.querySelector('div.month_selector').classList.toggle('hidden');
    }

    //select class
    document.querySelectorAll('.viewselector.selected').forEach(el=>el.classList.remove('selected'));
    target.classList.add('selected');

    //show view
    document.querySelectorAll('.calendarview').forEach(el=>el.classList.add('hidden'));
    document.getElementById(target.dataset.type).classList.remove('hidden');

    //change url
    url.searchParams.set('view', target.dataset.type.replace('view',''));
    window.history.pushState({}, '', url);
}

document.addEventListener("click", function(event) {
	let target = event.target;
    if(target.classList.contains('prevnext')){
        prevNext(target);
    }

    if(target.classList.contains('calendar-day')){
        event.stopPropagation();

        calendarDayClicked(target)
    }

    if(target.classList.contains('calendar-hour')){
        event.stopPropagation();
        hourClicked(target);
    }

    if(target.classList.contains('viewselector')){
        event.stopPropagation();
        viewChanged(target);
    }

    if(target.id=='add_calendar'){
        document.getElementById('calendaraddingoptions').classList.toggle('hidden');   
    }
    
    if(target.classList.contains('calendarurl')){
        event.stopPropagation();
        if(target.textContent != ''){
            navigator.clipboard.writeText(target.textContent);
            let options = {
                icon: 'success',
                title: 'Copied '+target.textContent,
                showConfirmButton: false,
                timer: 1500
            };

            if(document.fullscreenElement != null){
                options['target']	= document.fullscreenElement;
            }

            Swal.fire(options);
        }
    }
});

document.addEventListener("change", function(event) {
	let target = event.target;
    if(target.classList.contains('week_selector')){
        event.stopPropagation();

        let year    = target.closest('.date-search').querySelector('.year_selector').value;
        if(document.querySelector('.viewselector.selected').dataset.type=='weekview'){
            requestWeek(target, target.value, year);
        }else if(document.querySelector('.viewselector.selected').dataset.type=='listview'){
            requestExpandList(0,target.value,year);
        }

        //change url
        url.searchParams.set('week', target.value);
    }

    if(target.classList.contains('month_selector')){
        event.stopPropagation();

        let year    = target.closest('.date-search').querySelector('.year_selector').value;
        if(document.querySelector('.viewselector.selected').dataset.type=='monthview'){
            requestMonth(target, target.value, year);
        }else if(document.querySelector('.viewselector.selected').dataset.type=='listview'){
            requestExpandList(0,target.value, year);
        }
        url.searchParams.set('month', target.value);
    }

    if(target.classList.contains('year_selector')){
        event.stopPropagation();
        
        let month   = target.closest('.date-search').querySelector('.month_selector').value;
        if(document.querySelector('.viewselector.selected').dataset.type=='monthview'){
            requestMonth(target, month, target.value);
        }else if(document.querySelector('.viewselector.selected').dataset.type=='listview'){
            requestExpandList(0,month, target.value);
        }else if(document.querySelector('.viewselector.selected').dataset.type=='weekview'){
            let wknr  = target.closest('.date-search').querySelector('.week_selector').value;
            requestWeek(target, wknr, target.value);
        }
        url.searchParams.set('yr', target.value);
    }
    
    window.history.pushState({}, '', url);
});

window.onscroll = function() {
    let d       = document.documentElement;
    let offset  = d.scrollTop + window.innerHeight;
    let height  = d.offsetHeight;
  
    //if we scrolled to the bottom of the page and the list view is actve, and we are not currently loading, load more
    if (offset >= height-2 && document.querySelector('.viewselector.selected').dataset.type=='listview' && document.querySelector('#listview .loader') == null) {
        let skipcount  = document.querySelector("#listview").querySelectorAll('article').length;

        requestExpandList(skipcount);
    }
};

document.addEventListener('touchstart', handleTouchStart);        
document.addEventListener('touchmove', handleTouchMove);