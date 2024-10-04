import { __ } from '@wordpress/i18n';
import './editor.scss';
import {useState, useEffect} from "@wordpress/element";
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import {TimePicker, __experimentalRadio as Radio, __experimentalRadioGroup as RadioGroup, Button, SearchControl,  DatePicker, RadioControl, Spinner, CheckboxControl, __experimentalNumberControl as NumberControl} from "@wordpress/components";
import { store as coreDataStore } from '@wordpress/core-data';
import {useBlockProps} from "@wordpress/block-editor";

const Edit = ({ setAttributes, attributes } ) => {
	const blockProps = useBlockProps();
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	const [eventData, setEvent] = useState('');

	useEffect( 
		() => {
			if(meta[ 'eventdetails' ] != '' && meta[ 'eventdetails' ] != undefined){
				let data	= JSON.parse(meta[ 'eventdetails' ]);
				if(data.repeat == undefined){
					data.repeat	= {'interval':1};
				}

				if(data.starttime == undefined){
					data.starttime	= '00:00';
				}

				setEvent( data );

				if(data['organizer'] ){
					setLocationSearchTerm(data['organizer']);
				}

				if(data['location'] ){
					setLocationSearchTerm(data['location']);
				}
			}
		} ,
		[meta]
	);

	const updateMetaValue = ( key, value, label = '' ) => {
		let newEvent	= {...eventData};

		if(key == 'startdate'){
			newEvent['startdate']	= value.split('T')[0];

			if(newEvent['enddate'] == undefined || newEvent['enddate'] < newEvent['startdate']){
				newEvent['enddate']	= value.split('T')[0];
			}
		}else if( key == 'enddate'){
			newEvent['enddate']	= value.split('T')[0];
		}else if(key == 'starttime'){
			let time				= value.split('T')[1];
			time					= time.split(':');
			newEvent['starttime']	= time[0]+':'+time[1];
		}else if( key == 'endtime'){
			let time			= value.split('T')[1];
			time				= time.split(':');
			newEvent['endtime']	= time[0]+':'+time[1];
		}else if(key.startsWith("repeat")){
			if(newEvent['repeat'] == undefined){
				newEvent['repeat']	= {};
			}
			let subkey	= key.split('-')[1];

			if(subkey == 'excludedates' || subkey == 'includedates'){
				if(newEvent['repeat'][subkey] == undefined){
					newEvent['repeat'][subkey] = [];
				}

				let date = value.split('T')[0];

				let message = `added`;
				// remove
				if(newEvent['repeat'][subkey].includes(date)){
					newEvent['repeat'][subkey]   = newEvent['repeat'][subkey].filter(el => el != date);
					message = `removed`;
				}else{
					newEvent['repeat'][subkey].push(date)
				}

				wp.data.dispatch("core/notices").createNotice(
					"success", // Can be one of: success, info, warning, error.
					`Succesfully ${message} the date`, // Text string to display.
					{
					  type: "snackbar",
					  isDismissible: true, // Whether the user can dismiss the notice.
					}
				);
			}else if(subkey == 'weeks' || subkey == 'months'){
				if(newEvent['repeat'][subkey] == undefined){
					newEvent['repeat'][subkey] = [];
				}

				// remove
				if(newEvent['repeat'][subkey].includes(label)){
					newEvent['repeat'][subkey]   = newEvent['repeat'][subkey].filter(el => el != label);
				}else{
					newEvent['repeat'][subkey].push(label)
				}
			}else{
				newEvent['repeat'][subkey]	= value;
			}
		}else{
			newEvent[key]	= value;
		}

		if(key == 'organizer_id'){
			setUserSearchTerm(label);
			newEvent['organizer']	= label;
		}else if(key == 'location_id'){
			setLocationSearchTerm(label);
			newEvent['location']	= label;
		}
		
		newEvent	= JSON.stringify(newEvent);

		setMeta( { ...meta,  eventdetails: newEvent } );
	};	

	const [ userSearchTerm, setUserSearchTerm ]     		= useState( eventData['organizer'] );
	const [ locationSearchTerm, setLocationSearchTerm ]     = useState( eventData['location']);

	const { users, userResolved} = useSelect(
		( select) => {
			// do not show results if not searching
			if ( !userSearchTerm ) {
				return{
					users: [],
					userResolved: true
				}
			}

			// find all pages excluding the already selected pages
			const query = {
				search  : userSearchTerm,
				per_page: 100,
				context : 'view'
			};

			return {
				users: select( coreDataStore ).getUsers(query),
				userResolved: select( coreDataStore ).hasFinishedResolution(
					'getUsers',
					[query]
				)
			};
		},
		[userSearchTerm]
	);

	const { locations, locationResolved } = useSelect(
		( select) => {

			if(!locationSearchTerm){
				return {
					locations:	[],
					locationResolved:	true
				}
			}

			const query = {
				search  : locationSearchTerm,
				per_page: 100,
				context : 'view',
				orderby : 'relevance'
			};

			const args         = [ 'postType', 'location', query ];

			return {
				locations: select( coreDataStore ).getEntityRecords(
					...args
				),
				locationResolved: select( coreDataStore ).hasFinishedResolution(
					'getEntityRecords',
					args
				)
			};
		},
		[ locationSearchTerm ]
	);

	const BuildLocationRadioControls = function(){
		if ( ! locationResolved ) {
			return(
				<>
				<Spinner />
				<br></br>
				</>
			);
		}

		if(locationSearchTerm == '' || locationSearchTerm == undefined){
			return '';
		}
	
		if ( ! locations?.length ) {
			return <div> {__(`No location found`, 'sim')}</div>;
		}

		let options	= locations.map( c => (
			{ label: c.title.rendered, value: c.id }
		));
		
		return (
			<>
			<RadioControl
				selected= { parseInt(eventData['location_id'] ) }
				options = {options}
				onChange={ (value) => updateMetaValue('location_id', value, options.filter((option)=>{return(value==option.value)})[0].label)}
			/>
			</>
		)
	}

	const BuildUserRadioControls = function(){
		if ( ! userResolved ) {
			return(
				<>
				<Spinner />
				<br></br>
				</>
			);
		}

		if(userSearchTerm == '' || userSearchTerm == undefined){
			return '';
		}
	
		if ( ! users?.length ) {
			return <div> {__(`No user found`, 'sim')}</div>;
		}

		let options	= users.map( c => (
			{ label: c.name, value: c.id }
		));
		
		return (
			<>
			<RadioControl
				selected= { parseInt(eventData['organizer_id'] ) }
				options = {options}
				onChange={ (value) => updateMetaValue('organizer_id', value, options.filter((option)=>{return(value==option.value)})[0].label)}
			/>
			</>
		)
	}

	const CreateExcludeDatesControls  = () => {
		if(eventData.repeat.excludedates == undefined ){
			return ( '' )
		}

		return eventData.repeat.excludedates.map( (filter, index) =>
			<DatePicker
				currentDate={ eventData['repeat']['excludedates'][index] }
				value={ filter }
				onChange={ ( value ) => {updateMetaValue('repeat-excludedates', value, index)} }
			/>
		)
	};

	const DayRepeatDetails = () => {
		if( eventData.repeat.type != 'daily'){
			return '';
		}

		return (
			<>
			{__('Repeat interval in days')}
			<NumberControl
				onChange={ ( value ) => updateMetaValue('repeat-interval', value) }
				value={ eventData['repeat']['interval'] }
			/>
			<br></br>
			</>
		)
	}

	const WeekRepeatDetails = () => {
		if( eventData.repeat.type != 'weekly'){
			return '';
		}

		if(eventData['repeat']['weeks'] == undefined){
			eventData['repeat']['weeks'] = [];
		}

		let checkboxes	= ["First", "Second", "Third", "Fourth", "Fifth", "All"].map(t =>{
			return(<CheckboxControl
				label		= {__(t)}
				checked		={ eventData['repeat']['weeks'].includes(t) }
				onChange	={ (selected) => updateMetaValue('repeat-weeks', selected, t) }
			/>)
		});

		return (
			<>
			{__('Repeat interval in weeks')}
			<NumberControl
				onChange={ ( value ) => updateMetaValue('repeat-interval', value) }
				value={ eventData['repeat']['interval'] }
			/>
			<br></br>
			{__('Select weeks(s) of the month this event should be repeated on')}
			<br></br>
			<div class='flex week-selector'>
				{checkboxes}
			</div>
			<br></br>
			</>
		)
	}

	const MonthRepeatDetails = () => {
		if( eventData.repeat.type != 'monthly'){
			return '';
		}

		if(eventData['repeat']['months'] == undefined){
			eventData['repeat']['months'] = [];
		}

		let startDate	= new Date(eventData['startdate']);
		let weekDays	= [
			'Sunday',
			'Monday',
			'Tuesday',
			'Wednesday',
			'Thursday',
			'Friday',
			'Saturday'
		]

		let weekDay	= weekDays[startDate.getDay()];
		let weekNr	= parseInt(startDate.getDate()/7);

		console.log(weekNr)
		let nrInWords	= [
			'first',
			'second',
			'third',
			'fourth',
			'fifth'
		]
		let weekWord	= nrInWords[weekNr];
		return (
			<>
			{__('Select repeat type')}
			<br></br>
			<RadioGroup label="Width" onChange={ (value) => updateMetaValue('repeat-datetype', value) } checked={ eventData['repeat']['datetype'] }>
				<Radio value="samedate">{__('On the same day')}</Radio>
				<Radio value="patterned">{__(`On the ${weekWord} ${weekDay} of the month`)}</Radio>
				<Radio value="last">{__(`On the last ${weekDay} of the month`)}</Radio>
				<Radio value="lastday">{__(`On the last day of the month`)}</Radio>
			</RadioGroup>
			<br></br>
			<br></br>
			< MonthRepeatPattern />
			</>	
		)
	}

	const MonthRepeatPattern	= () => {
		if(eventData['repeat']['datetype'] == undefined){
			return '';
		}else if(eventData['repeat']['datetype'] == 'samedate'){
			return (
				<>
				{__('Repeat interval in months')}
				<NumberControl
					onChange={ ( value ) => updateMetaValue('repeat-interval', value) }
					value={ eventData['repeat']['interval'] }
				/>
				<br></br>
				</>
			);
		}

		let checkboxes	= ["All", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"].map(t =>{
			return(<CheckboxControl
				label		= {__(t)}
				checked		={ eventData['repeat']['months'].includes(t) }
				onChange	={ (value) => updateMetaValue('repeat-months', value, t) }
			/>)
		});
		let checkboxes1	= checkboxes.splice(0,1);
		let checkboxes2	= checkboxes.splice(0,6);

		return (
			<>
				{__('Select month(s) this event should be repeated')}
				<br></br>
				<div class='flex month-selector'>
					{checkboxes1}
				</div>
				<div class='flex month-selector'>
					{checkboxes2}
				</div>
				<div class='flex month-selector'>
					{checkboxes}
				</div>
				<br></br>
			</>
		)
	}

	const CreateIncludeDatesControls  = () => {
		if( eventData.repeat.type != 'custom_days'){
			return '';
		}

		let datePicker	= <DatePicker
			currentDate={ null }
			onChange={ ( value ) => {updateMetaValue('repeat-includedates', value)} }
		/>

		if(eventData.repeat.includedates == undefined ){
			return ( datePicker )
		}

		return (<>
			{eventData.repeat.includedates.map( (filter, index) =>
				<DatePicker
					currentDate={ eventData['repeat']['includedates'][index] }
					value={ filter }
					onChange={ ( value ) => {updateMetaValue('repeat-includedates', value, index)} }
				/>
			)}
			{datePicker};
			</>
		)
	};

	const StopDetails	= () => {
		if(	eventData['repeat']['stop'] == undefined || eventData['repeat']['stop'] == 'never'){
			return '';
		}

		if(eventData['repeat']['stop'] == 'date'){
			return (
				<>
				{__('Last date')}
				<DatePicker
					currentDate={ eventData['repeat']['enddate']  }
					onChange={ ( value ) => updateMetaValue('repeat-enddate', value) }
					__nextRemoveHelpButton
					__nextRemoveResetButton
				/>
				</>
			)
		}
		
		return (
			<>
			{__('Maximum occurences')}
			<NumberControl
				onChange={ ( value ) => updateMetaValue('repeat-amount', value) }
				value={ eventData['repeat']['amount'] }
			/>
			<br></br>
			</>
		)
	}

	const EventRepeatDetails	= () => {
		if(	eventData['isrepeated'] == undefined || !eventData['isrepeated']){
			return '';
		}

		return (
			<>
			<br></br>
			<div id='event-repeat-details'>
				{__('Select repetition type')}
				<br></br>
				<RadioGroup label="Width" onChange={ (value) => updateMetaValue('repeat-type', value) } checked={ eventData['repeat']['type'] }>
					<Radio value="daily">Daily</Radio>
					<Radio value="weekly">Weekly</Radio>
					<Radio value="monthly">Monthly</Radio>
					<Radio value="yearly">Yearly</Radio>
					<Radio value="custom_days">Custom Days</Radio>
				</RadioGroup>
				<br></br>
				<br></br>
				< DayRepeatDetails />
				< WeekRepeatDetails />
				< MonthRepeatDetails />
				< CreateIncludeDatesControls />

				{__('Stop repetition')}
				<RadioControl
					selected= { eventData['repeat']['stop'] }
					options = {[
						{ label: 'Never', value: 'never' },
						{ label: 'On this date', value: 'date' },
						{ label: 'After this amount of repeats', value: 'after' }
					]}
					onChange={ (value) => updateMetaValue('repeat-stop', value)}
				/>
				{ StopDetails() }


				<br></br>
				{__('Exclude dates, click to select or deselect')}
				< CreateExcludeDatesControls />

				<DatePicker
					currentDate={ null }
					onChange={ ( value ) => updateMetaValue('repeat-excludedates', value, 0) }
					__nextRemoveHelpButton
					__nextRemoveResetButton
				/>
			</div>
			</>
		)

	}

	const EventDetails	= () => {
		if(
			(eventData['endtime'] == undefined && (eventData['allday'] == undefined || !eventData['allday'])) || 
			(eventData['enddate'] == eventData['startdate'] && eventData['endtime'] <= eventData['starttime'] && (eventData['allday'] == undefined  || !eventData['allday']))
		){
			return '';
		}

		return (
			<>

			<i>{__('Use searchbox below to search for a location', 'sim')}</i>
            < SearchControl 
				onChange={ setLocationSearchTerm } 
				value={ locationSearchTerm } 
			/>
            < BuildLocationRadioControls />
			<br></br>

			<i>{__('Use searchbox below to search for an user to add as organizer', 'sim')}</i>
            < SearchControl 
				onChange={ setUserSearchTerm } 
				value={ userSearchTerm } 
			/>
            < BuildUserRadioControls />

			<br></br>
			<Button variant="primary" className='repeat-button' onClick={(event) => updateMetaValue('isrepeated', !eventData['isrepeated'])}>Repeat this event</Button>
			<br></br>
			{ EventRepeatDetails() }
			</>
		)
	}

	const TimeControls	= () => {

		if(eventData['startdate'] == undefined ){
			return '';
		}

		const TimePickers	= () => {
			if(eventData['allday'] ){
				return '';
			}

			let startTime	= eventData['starttime'] == undefined ?'00:00:00':eventData['starttime'];
			let endTime		= eventData['endtime'] == undefined ?'00:00:00':eventData['endtime'];

			return (
				<>
					<div>
						<span class='center'>{__('Start time', 'sim')}</span>
						<TimePicker
							currentTime={"1986-10-18T"+startTime}
							onChange={ (value) => updateMetaValue('starttime', value) }
							__nextRemoveHelpButton
							__nextRemoveResetButton
						/>
					</div>
					<div>
						<span class='center'>{__('End time', 'sim')}</span>
						<TimePicker
							currentTime={"1986-10-18T"+endTime}
							onChange={ (value) => updateMetaValue('endtime', value) }
							__nextRemoveHelpButton
							__nextRemoveResetButton
						/>
					</div>
				</>
			)
		}

		return (
			<>
			<div class='time-pickers flex'>
				< TimePickers />
				<CheckboxControl
					label		= {__('This is an all day event')}
					onChange	= {(value) => updateMetaValue('allday', value)}
					checked		= {eventData['allday']}
				/>
			</div>
			</>
		)
	}

	return (
		<div { ...blockProps }>
			<h2>{__('Event Details')}</h2>
			<div class='date-pickers flex'>
				<div>
					<span class='center'>{__('Start date', 'sim')}</span>
					<DatePicker
						currentDate={ eventData['startdate']?eventData['startdate']:null }
						onChange={ (value) => updateMetaValue('startdate', value) }
						//events={[new Date('2022-08-20')]}
						__nextRemoveHelpButton
						__nextRemoveResetButton
					/>
				</div>

				<div>
					<span class='center'>{__('End date', 'sim')}</span>
					<DatePicker
						currentDate={ eventData['enddate']?eventData['enddate']:null }
						onChange={ (value) => updateMetaValue('enddate', value) }
						//events={[new Date(), new Date('2022-08-20')]}
						__nextRemoveHelpButton
						__nextRemoveResetButton
					/>
				</div>
			</div>

			< TimeControls />

			{ EventDetails() }
		</div>
	);
}

export default Edit;

