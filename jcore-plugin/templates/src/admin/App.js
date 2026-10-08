import apiFetch from '@wordpress/api-fetch';
import {
	Card,
	CardBody,
	Notice,
	Spinner,
	ToggleControl,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const PATH = '/{{slug}}/v1/settings';

export default function App() {
	const [ settings, setSettings ] = useState( null );
	const [ error, setError ] = useState( null );

	useEffect( () => {
		apiFetch( { path: PATH } )
			.then( setSettings )
			.catch( ( e ) => setError( e.message ) );
	}, [] );

	const update = ( changes ) => {
		setSettings( { ...settings, ...changes } );
		apiFetch( { path: PATH, method: 'POST', data: changes } )
			.then( setSettings )
			.catch( ( e ) => setError( e.message ) );
	};

	return (
		<div className="wrap {{slug}}-admin">
			<h1>{ __( '{{name}}', '{{slug}}' ) }</h1>
			{ error && (
				<Notice status="error" onRemove={ () => setError( null ) }>
					{ error }
				</Notice>
			) }
			<Card>
				<CardBody>
					{ settings ? (
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Enabled', '{{slug}}' ) }
							checked={ settings.enabled }
							onChange={ ( enabled ) => update( { enabled } ) }
						/>
					) : (
						<Spinner />
					) }
				</CardBody>
			</Card>
		</div>
	);
}
