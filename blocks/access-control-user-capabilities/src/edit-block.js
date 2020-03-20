import { compose, withState } from '@wordpress/compose';
import UserCapabilities from './user-capabilities';
import { withAccessControlGroup } from '../../../packages/components/src';

/**
 * Same constant as in \PoP\UserRolesAccessControl\Services\AccessControlGroups::CAPABILITIES
 */
const ACCESS_CONTROL_GROUP = 'capabilities';

export default compose( [
	withState( {
		accessControlGroup: ACCESS_CONTROL_GROUP,
	} ),
	withAccessControlGroup(),
] )( UserCapabilities );