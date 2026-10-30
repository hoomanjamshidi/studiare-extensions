/**
 * Studiare Extensions — cart & checkout panel: the billing field list.
 *
 * WooCommerce's fields bind through admin.js (`data-stx-bind`). This script
 * adds what a list needs: the order (`fields.order`, by dragging or the
 * arrows), a summary of each row (label and chips), and the admin's own
 * fields (`fields.custom`), whose rows are cloned from the template and bound
 * with `data-custom-bind` (admin.js only binds markup present at load).
 */
( function ( $ ) {
	'use strict';

	const STX = window.STX;
	const root = document.querySelector( '[data-stx-module="checkout"]' );
	if ( ! STX || ! STX.store || ! root ) {
		return;
	}

	const store = STX.store;
	const moduleData = STX.data.moduleData;
	const i18n = Object.assign( {}, STX.i18n, moduleData.i18n || {} );
	const list = root.querySelector( '[data-stx-co-fields]' );
	const template = root.querySelector( '[data-stx-co-template]' );

	const rows = () => Array.from( list.querySelectorAll( '.stx-item[data-field]' ) );
	const isCustom = ( key ) => key.indexOf( moduleData.customPrefix ) === 0;
	const customFields = () => store.get( 'fields.custom' ) || [];
	const findCustom = ( id ) => customFields().find( ( field ) => field.id === id );

	/**
	 * @param {string} text Chip text.
	 * @param {string} tone `accent`, `warn` or ''.
	 */
	function chip( text, tone ) {
		const span = document.createElement( 'span' );
		span.className = 'stx-chip' + ( tone ? ' stx-chip--' + tone : '' );
		span.textContent = text;
		return span;
	}

	/** The row's label, chips and off state, from the stored settings. */
	function refreshRow( row ) {
		const key = row.dataset.field;
		const custom = isCustom( key );
		const field = custom ? findCustom( key ) : store.get( 'fields.billing.' + key );
		if ( ! field ) {
			return;
		}

		row.querySelector( '[data-field-label]' ).textContent = field.label || ( custom ? i18n.newField : moduleData.labels[ key ] );
		row.classList.toggle( 'is-off', ! field.enabled );

		const chips = row.querySelector( '[data-field-chips]' );
		const required = custom ? ( field.required ? 'yes' : 'no' ) : field.required;
		chips.textContent = '';

		if ( ! field.enabled ) {
			chips.append( chip( i18n.hidden, '' ) );
		} else {
			if ( 'yes' === required ) {
				chips.append( chip( i18n.required, 'accent' ) );
			} else if ( 'no' === required ) {
				chips.append( chip( i18n.optional, '' ) );
			}
			if ( ! field.digital ) {
				chips.append( chip( i18n.shippedOnly, '' ) );
			}
			if ( 'half' === field.width ) {
				chips.append( chip( i18n.half, '' ) );
			}
			if ( custom && ! field.label ) {
				chips.append( chip( i18n.needsName, 'warn' ) );
			}
			if ( custom && 'select' === field.type && ! String( field.options || '' ).trim() ) {
				chips.append( chip( i18n.needsChoices, 'warn' ) );
			}
		}

		if ( custom ) {
			refreshCustomControls( row, field );
		}
	}

	/** Writes a custom field's values into its row (never into the control being typed in). */
	function refreshCustomControls( row, field ) {
		row.querySelectorAll( '[data-custom-bind]' ).forEach( ( control ) => {
			const value = field[ control.dataset.customBind ];
			if ( 'checkbox' === control.type ) {
				control.checked = Boolean( value );
			} else if ( 'radio' === control.type ) {
				control.checked = control.value === value;
			} else if ( document.activeElement !== control ) {
				control.value = null == value ? '' : value;
			}
		} );

		row.querySelectorAll( '[data-custom-show-if]' ).forEach( ( node ) => {
			node.hidden = node.dataset.customShowIf !== field.type;
		} );
	}

	/** Adds rows for new custom fields and removes the rows of deleted ones. */
	function syncCustomRows() {
		const ids = customFields().map( ( field ) => field.id );

		rows().filter( ( row ) => isCustom( row.dataset.field ) && ! ids.includes( row.dataset.field ) ).forEach( ( row ) => row.remove() );

		const present = rows().map( ( row ) => row.dataset.field );
		ids.filter( ( id ) => ! present.includes( id ) ).forEach( ( id ) => {
			const holder = document.createElement( 'template' );
			holder.innerHTML = template.innerHTML.replace( /__ID__/g, id );
			list.appendChild( holder.content.firstElementChild );
		} );
	}

	/** Puts the rows in the stored order (after reordering, reset or save). */
	function syncOrder() {
		const order = store.get( 'fields.order' ) || [];
		if ( rows().map( ( row ) => row.dataset.field ).join() === order.join() ) {
			return;
		}

		const byKey = {};
		rows().forEach( ( row ) => {
			byKey[ row.dataset.field ] = row;
		} );
		order.forEach( ( key ) => byKey[ key ] && list.appendChild( byKey[ key ] ) );
	}

	function refresh() {
		syncCustomRows();
		syncOrder();
		rows().forEach( refreshRow );
	}

	function setOpen( row, open ) {
		row.classList.toggle( 'is-open', open );
		row.querySelector( '.stx-item__editor' ).hidden = ! open;
		row.querySelector( '.stx-item__summary' ).setAttribute( 'aria-expanded', String( open ) );
	}

	function move( key, step ) {
		const order = ( store.get( 'fields.order' ) || [] ).slice();
		const index = order.indexOf( key );
		const target = index + step;
		if ( index < 0 || target < 0 || target >= order.length ) {
			return;
		}
		order.splice( target, 0, order.splice( index, 1 )[ 0 ] );
		store.set( 'fields.order', order );
	}

	/**
	 * @param {string} id      Custom field key.
	 * @param {Object} changes Settings to change.
	 */
	function updateCustom( id, changes ) {
		const fields = STX.clone( customFields() );
		const field = fields.find( ( entry ) => entry.id === id );
		if ( field ) {
			Object.assign( field, changes );
			store.set( 'fields.custom', fields );
		}
	}

	function addCustom() {
		const fields = STX.clone( customFields() );
		if ( fields.length >= moduleData.maxCustom ) {
			STX.toast( i18n.maxReached, 'error' );
			return;
		}

		const id = moduleData.customPrefix + Math.random().toString( 36 ).slice( 2, 10 );
		fields.push( Object.assign( STX.clone( moduleData.customDefaults ), { id } ) );
		store.set( 'fields.custom', fields );
		store.set( 'fields.order', ( store.get( 'fields.order' ) || [] ).concat( [ id ] ) );

		const row = list.querySelector( '[data-field="' + id + '"]' );
		setOpen( row, true );
		row.scrollIntoView( { behavior: 'smooth', block: 'center' } );
		row.querySelector( '[data-custom-bind="label"]' ).focus( { preventScroll: true } );
	}

	async function deleteCustom( id ) {
		const field = findCustom( id );
		const confirmed = await STX.confirm( {
			title: i18n.deleteTitle,
			message: i18n.deleteMessage.replace( '%s', field.label || i18n.newField ),
			confirmLabel: i18n.delete,
			danger: true,
		} );
		if ( ! confirmed ) {
			return;
		}

		store.set( 'fields.custom', customFields().filter( ( entry ) => entry.id !== id ) );
		store.set( 'fields.order', ( store.get( 'fields.order' ) || [] ).filter( ( key ) => key !== id ) );
	}

	/** Control changes in custom rows (WooCommerce's rows bind through admin.js). */
	function onCustomInput( event ) {
		const control = event.target.closest( '[data-custom-bind]' );
		if ( ! control || ( 'radio' === control.type && ! control.checked ) ) {
			return;
		}
		updateCustom( control.closest( '.stx-item' ).dataset.field, {
			[ control.dataset.customBind ]: 'checkbox' === control.type ? control.checked : control.value,
		} );
	}

	list.addEventListener( 'click', ( event ) => {
		const row = event.target.closest( '.stx-item' );
		if ( ! row ) {
			return;
		}

		if ( event.target.closest( '[data-item-toggle]' ) ) {
			setOpen( row, ! row.classList.contains( 'is-open' ) );
			return;
		}

		const mover = event.target.closest( '[data-field-move]' );
		if ( mover ) {
			move( row.dataset.field, 'up' === mover.dataset.fieldMove ? -1 : 1 );
			mover.focus();
			return;
		}

		if ( event.target.closest( '[data-custom-action="delete"]' ) ) {
			deleteCustom( row.dataset.field );
		}
	} );

	list.addEventListener( 'input', onCustomInput );
	list.addEventListener( 'change', onCustomInput );
	root.querySelector( '[data-stx-co-add]' ).addEventListener( 'click', addCustom );

	if ( $ && $.fn.sortable ) {
		$( list ).sortable( {
			handle: '.stx-item__handle',
			items: '> .stx-item',
			axis: 'y',
			tolerance: 'pointer',
			placeholder: 'stx-item-placeholder',
			update() {
				store.set( 'fields.order', rows().map( ( row ) => row.dataset.field ) );
			},
		} );
	}

	store.subscribe( refresh );
	refresh();
}( window.jQuery ) );
