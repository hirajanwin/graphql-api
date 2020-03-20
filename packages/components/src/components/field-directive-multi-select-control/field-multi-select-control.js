/**
 * WordPress dependencies
 */
import { withSelect } from '@wordpress/data';
import { compose, withState } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import MultiSelectControl from '../multi-select-control';
import { TYPE_FIELD_SEPARATOR_FOR_DB } from './block-constants';

const FieldMultiSelectControl = compose( [
	withState( { attributeName: 'typeFields' } ),
	withSelect( ( select ) => {
		const {
			getTypeFields,
			hasRetrievedTypeFields,
			getRetrievingTypeFieldsErrorMessage,
		} = select ( 'graphql-api/components' );
		/**
		 * Convert typeFields object, from this structure:
		 * [{type:"Type", fields:["field1", "field2",...]},...]
		 * To this one:
		 * [{group:"typeName",title:"field1",value:"typeName/field1"},...]
		 */
		const items = getTypeFields().flatMap(function(typeItem) {
			return typeItem.fields.flatMap(function(field) {
				return [{
					group: typeItem.typeName,
					title: field,
					value: `${ typeItem.typeNamespacedName }${ TYPE_FIELD_SEPARATOR_FOR_DB }${ field }`,
				}]
			})
		});
		return {
			items,
			hasRetrievedItems: hasRetrievedTypeFields(),
			errorMessage: getRetrievingTypeFieldsErrorMessage(),
		};
	} ),
] )( MultiSelectControl );

export default FieldMultiSelectControl;
