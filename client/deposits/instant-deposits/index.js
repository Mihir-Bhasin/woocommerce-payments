/** @format **/

/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './style.scss';
import InstantDepositModal from './modal';
import { useInstantDeposit } from 'data';

const InstantDepositButton = ( {
	balance: { amount, fee, net, transaction_ids: transactionIds },
} ) => {
	const [ isModalOpen, setModalOpen ] = useState( false );
	const { inProgress, submit } = useInstantDeposit( transactionIds );
	const onClose = () => {
		setModalOpen( false );
	};
	const onSubmit = () => {
		setModalOpen( false );
		submit();
	};
	// TODO: Need to update isDefault to isSecondary once @wordpress/components is updated
	// https://github.com/Automattic/woocommerce-payments/pull/1536
	return (
		<>
			<Button
				isDefault
				className="is-secondary"
				onClick={ () => setModalOpen( true ) }
			>
				{ __( 'Instant deposit', 'woocommerce-payments' ) }
			</Button>
			{ ( isModalOpen || inProgress ) && (
				<InstantDepositModal
					amount={ amount }
					fee={ fee }
					net={ net }
					inProgress={ inProgress }
					onSubmit={ onSubmit }
					onClose={ onClose }
				/>
			) }
		</>
	);
};

export default InstantDepositButton;