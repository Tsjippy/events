import { __ } from '@wordpress/i18n';
import {useBlockProps, InspectorControls} from "@wordpress/block-editor";
import './editor.scss';
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";
import {TextControl , Spinner, Panel, PanelBody, __experimentalNumberControl as NumberControl, CheckboxControl} from "@wordpress/components";

const Edit = ({attributes, setAttributes}) => {
	const {title, months, hide} = attributes;

	const [html, setHtml] = useState(<>Loading < Spinner /></>);

	useEffect( 
		() => {
			async function getHtml(){

				setHtml( <>Loading < Spinner /></> );

				const response = await apiFetch({
					path: sim.restApiPrefix+'/events/upcoming_arrivals',
					method: 'POST',
					data: { 
						title: title,
						months: months
					},
				});

				console.log(response)
				setHtml( wp.element.RawHTML( { children: response }) );
			}
			getHtml();
		} ,
		[title, months]
	);

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
						<NumberControl
							label		= { __("Timespan in months", "sim") }
							value		= { months || 2 }
							onChange	= { (val) => setAttributes({months: parseInt(val)}) }
							min			= { 1 }
							max			= { 12 }
						/>
						<CheckboxControl
							key			=  'hide'
							label		= { __('Hide if no arrivals') }
							onChange	= { (val) => setAttributes({hide: val}) }
							checked		= { hide }
						/>
					</PanelBody>
				</Panel>
			</InspectorControls>
			<div {...useBlockProps()}>
				{html}
			</div>
		</>
	);
}

export default Edit;
