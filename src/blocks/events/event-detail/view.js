/**
 * Event detail: confirm before the delete form submits (Interactivity API).
 */
import { store, getElement } from '@wordpress/interactivity';

store( 'clanbite-event-detail', {
	actions: {
		/**
		 * Cancel the submit unless the visitor confirms the message in `data-clanbite-confirm`.
		 *
		 * @param {SubmitEvent} event Form submit event.
		 */
		confirmDelete( event ) {
			const { ref } = getElement();
			const message = ref?.dataset?.clanbiteConfirm;
			// Native confirm is accessible and keeps the delete form usable without extra markup.
			// eslint-disable-next-line no-alert
			if ( message && ! window.confirm( message ) ) {
				event.preventDefault();
			}
		},
	},
} );
