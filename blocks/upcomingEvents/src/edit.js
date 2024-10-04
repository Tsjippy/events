import { __ } from '@wordpress/i18n';
import {useBlockProps, InspectorControls} from "@wordpress/block-editor";
import './editor.scss';
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";
import { TextControl, Spinner, Panel, PanelBody, CheckboxControl, __experimentalNumberControl as NumberControl} from "@wordpress/components";

const Edit = ({attributes, setAttributes}) => {
	const { items, months, categories, title } = attributes; 

	const onCatChanged	= function(checked){
		let copy =	{ ...categories }

		// this is the cat id
		copy[this]	= checked;
		setAttributes({categories: copy});
	}

	const [cats, setCats] = useState( < Spinner /> );

	useEffect( 
		() => {
			async function buildCheckboxes(){
				setCats( <Spinner />);
				const fetchedCats = await apiFetch({path: "/wp/v2/events"});

				setCats( fetchedCats.map( c => (
					<CheckboxControl
						key			= {c.id}
						label		= {c.name}
						onChange	= {onCatChanged.bind(c.id)}
						checked		= {categories[c.id]}
					/>
				)));
			}
			buildCheckboxes();
		} , 
		[attributes.categories]
	);


	// variable, function name to set variable
	const [events, storeEvents] = useState([]);

	const fetchEvents = async () => {		
		let param	= '';
		
		if(items != undefined){
			param += "?items"+items;
		}

		if(months != undefined){
			if(param == ''){
				param += "?";
			}else{
				param += "&";
			}
			param += "months="+months;
		}

		if(categories != undefined){
			if(param == ''){
				param += "?";
			}else{
				param += "&";
			}
			param += "categories=";
			for (const key in categories) {param += key+','}
		}
		
		let fetchedEvents = await apiFetch({path: `sim/v2/events/upcoming_events${param}`});

		if(!fetchedEvents){
			fetchedEvents	= [];
		}
		storeEvents(fetchedEvents);
	}

	useEffect( () => { fetchEvents(); }, [items, months, categories]);

	const buildHtml	= () => {

		if ( events.length === 0 ) {
			return <p>No events found!</p>;
		}

		return (
			events.map(event => {
				return (
					<article className="event-article" key={event.id}>
						<div className="event-wrapper">
							<div className="event-date">
								<span>{event.day}</span> {event.month}
							</div>
							<div>
								<h4 className="event-title">
									<a href={event.url}>
										{event.title}
									</a>
								</h4>
								<div className="event-detail">
									{event.time}
								</div>
							</div>
						</div>
					</article>
				);
			})
		)
	}

	return (
		<>
			<InspectorControls>
				<Panel>
					<PanelBody>
						<TextControl
							label		= "Block title"
							value		= { title }
							onChange	= { (val) => setAttributes({title: val}) }
						/>
						Select an category you want to exclude from the list
						{cats}
						<NumberControl
							label		= {__("Select the maximum amount of events", "sim")}
							value		= {items || 10}
							onChange	= {(val) => setAttributes({items: parseInt(val)})}
							min			= {1}
							max			= {20}
						/>
						<NumberControl
							label		= {__("Select the range in months we will retrieve", "sim")}
							value		= {months || 2}
							onChange	= {(val) => setAttributes({months: parseInt(val)})}
							min			= {1}
							max			= {12}
						/>
					</PanelBody>
				</Panel>
			</InspectorControls>
			<div {...useBlockProps()}>
				<aside className='event'>
					<h4 className="title">{title}</h4>
					<div className="upcomingevents_wrapper">
						{buildHtml()}
					</div>
					<a className='calendar button sim' href="./events">
						Calendar
					</a>
				</aside>
			</div>
		</>
	);
}

export default Edit;
