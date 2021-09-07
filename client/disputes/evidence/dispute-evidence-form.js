/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Button,
	Card,
	CardBody,
	CardFooter,
	CardHeader,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { useMemo } from '@wordpress/element';

import { flatten } from 'lodash';
import moment from 'moment';

/**
 * Internal dependencies.
 */
import evidenceFields from './fields';
import { FileUploadControl } from './file-upload';
import { useDispute, useDisputeEvidence } from 'wcpay/data';
import { getDisputeProductType } from '../helpers';

/* If description is an array, separate with newline elements. */
const expandHelp = ( description ) => {
	return Array.isArray( description )
		? flatten(
				description.map( ( line, i ) => [ line, <br key={ i } /> ] )
		  )
		: description;
};

export const DisputeEvidenceForm = ( props ) => {
	const { readOnly, disputeId } = props;

	const { dispute, updateDispute } = useDispute( disputeId );

	const {
		evidenceTransient,
		evidenceUploadErrors,
		isSavingEvidence,
		isUploadingEvidence,
		updateEvidenceTransientForDispute,
		uploadFileEvidenceForDispute,
		updateEvidenceUploadErrorsForDispute,
		saveEvidence,
		submitEvidence,
	} = useDisputeEvidence( disputeId );

	const productType = getDisputeProductType( dispute );
	const disputeReason = dispute && dispute.reason;
	const fields = useMemo(
		() => evidenceFields( disputeReason, productType ),
		[ disputeReason, productType ]
	);

	if ( ! fields || ! fields.length ) {
		return null;
	}

	const evidence = dispute
		? {
				...dispute.evidence,
				...evidenceTransient,
				metadata: dispute.metadata || {},
				isUploading: isUploadingEvidence,
				uploadingErrors: evidenceUploadErrors,
		  }
		: {};

	/*
	 * Event handlers.
	 */
	const updateEvidence = ( key, value ) => {
		updateEvidenceTransientForDispute( disputeId, {
			...evidenceTransient,
			[ key ]: value,
		} );
	};
	const doRemoveFile = ( key ) => {
		updateEvidence( key, '' );
		updateDispute( {
			...dispute,
			metadata: { ...dispute.metadata, [ key ]: '' },
			fileSize: { ...dispute.fileSize, [ key ]: 0 },
		} );
		updateEvidenceUploadErrorsForDispute( disputeId, {
			...evidenceUploadErrors,
			[ key ]: '',
		} );
	};
	const doUploadFile = async ( key, file ) => {
		uploadFileEvidenceForDispute( disputeId, key, file );
	};

	/*
	 * Functions used to compose props for components.
	 */
	const composeDefaultControlProps = ( field ) => ( {
		label: field.label,
		value: evidence[ field.key ] || '',
		onChange: ( value ) => updateEvidence( field.key, value ),
		disabled: readOnly,
		help: expandHelp( field.description ),
	} );
	const composeFileUploadProps = ( field ) => {
		const fileName =
			( evidence.metadata && evidence.metadata[ field.key ] ) || '';
		const isLoading =
			evidence.isUploading &&
			( evidence.isUploading[ field.key ] || false );
		const error =
			evidence.uploadingErrors &&
			( evidence.uploadingErrors[ field.key ] || '' );
		const isDone = ! isLoading && 0 < fileName.length;
		const accept = '.pdf, image/png, image/jpeg';
		return {
			field,
			fileName,
			accept,
			onFileChange: doUploadFile,
			onFileRemove: doRemoveFile,
			disabled: readOnly,
			isLoading,
			isDone,
			error,
			help: expandHelp( field.description ),
		};
	};
	const composeFieldControl = ( field ) => {
		switch ( field.type ) {
			case 'file':
				return (
					<FileUploadControl
						key={ field.key }
						{ ...composeFileUploadProps( field ) }
					/>
				);
			case 'text':
				return (
					<TextControl
						key={ field.key }
						{ ...composeDefaultControlProps( field ) }
					/>
				);
			case 'date':
				return (
					<TextControl
						key={ field.key }
						type={ 'date' }
						max={ moment().format( 'YYYY-MM-DD' ) }
						{ ...composeDefaultControlProps( field ) }
					/>
				);
			default:
				return (
					<TextareaControl
						key={ field.key }
						{ ...composeDefaultControlProps( field ) }
					/>
				);
		}
	};

	/*
	 * Construct the evidence component sections.
	 */
	const evidenceSections = fields.map( ( section ) => {
		return (
			<Card size="large" key={ section.key }>
				<CardHeader>{ section.title }</CardHeader>
				<CardBody>
					{ section.description && <p>{ section.description }</p> }
					{ section.fields.map( composeFieldControl ) }
				</CardBody>
			</Card>
		);
	} );

	const confirmMessage = __(
		"Are you sure you're ready to submit this evidence? Evidence submissions are final.",
		'woocommerce-payments'
	);
	const handleSubmit = () =>
		window.confirm( confirmMessage ) &&
		submitEvidence( dispute.id, evidenceTransient );

	return (
		<>
			{ evidenceSections }
			{ readOnly ? null : (
				<Card size="large">
					<CardBody>
						<p>
							{ __(
								// eslint-disable-next-line max-len
								"When you submit your evidence, we'll format it and send it to the cardholder's bank, then email you once the dispute has been decided.",
								'woocommerce-payments'
							) }
						</p>
						<p>
							<strong>
								{ __(
									'Evidence submission is final.',
									'woocommerce-payments'
								) }
							</strong>{ ' ' }
							{ __(
								'You can also save this evidence for later instead of submitting it immediately.',
								'woocommerce-payments'
							) }{ ' ' }
							<strong>
								{ __(
									'We will automatically submit any saved evidence at the due date.',
									'woocommerce-payments'
								) }
							</strong>
						</p>
					</CardBody>
					<CardFooter>
						{ /* Use wrapping div to keep buttons grouped together. */ }
						<div>
							<Button isPrimary onClick={ handleSubmit }>
								{ __(
									'Submit evidence',
									'woocommerce-payments'
								) }
							</Button>
							<Button
								isSecondary
								onClick={ () =>
									saveEvidence(
										dispute.id,
										evidenceTransient
									)
								}
								isBusy={ isSavingEvidence }
							>
								{ __(
									'Save for later',
									'woocommerce-payments'
								) }
							</Button>
						</div>
					</CardFooter>
				</Card>
			) }
		</>
	);
};
