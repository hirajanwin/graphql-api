/**
 * WordPress dependencies
 */
import { withSelect } from '@wordpress/data';
import { compose, withState } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import MultiSelectControl from '../multi-select-control';
import AddUndefinedSelectedItemIDs from '../multi-select-control/add-undefined-selected-item-ids';

const CacheControlListMultiSelectControl = compose( [
	withState( { attributeName: 'cacheControlLists' } ),
	withSelect( ( select ) => {
		const {
			getCacheControlLists,
			hasRetrievedCacheControlLists,
			getRetrievingCacheControlListsErrorMessage,
		} = select ( 'graphql-api/components' );
		/**
		 * Convert the cacheControlLists array to this structure:
		 * [{group:"CacheControlLists",title:"cacheControlList.title",value:"cacheControlList.id"},...]
		 */
		const items = getCacheControlLists().map( cacheControlList => (
			{
				group: __('Cache Control Lists', 'graphql-api'),
				title: cacheControlList.title,
				value: cacheControlList.id,
			}
		) );
		return {
			items,
			hasRetrievedItems: hasRetrievedCacheControlLists(),
			errorMessage: getRetrievingCacheControlListsErrorMessage(),
		};
	} ),
	AddUndefinedSelectedItemIDs,
] )( MultiSelectControl );

export default CacheControlListMultiSelectControl;
