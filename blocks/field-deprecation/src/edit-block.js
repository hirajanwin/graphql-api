import { __ } from '@wordpress/i18n';
import { compose, withState } from '@wordpress/compose';
import FieldDeprecation from './field-deprecation';
import { withFieldDirectiveMultiSelectControl } from '../../../packages/components/src';

export default compose( [
	withState( {
		disableDirectives: true,
		// disableHeader: true,
		fieldHeader: __('Fields to deprecate', 'graphql-api'),
	} ),
	withFieldDirectiveMultiSelectControl(),
] )( FieldDeprecation );