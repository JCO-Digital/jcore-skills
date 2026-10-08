import { createRoot } from '@wordpress/element';
import App from './App';
import './style.scss';

const root = document.getElementById( '{{slug}}-app' );
if ( root ) {
	createRoot( root ).render( <App /> );
}
