/**
 * External dependencies
 */
import { View } from 'react-native';

/**
 * WordPress dependencies
 */
import { withSelect } from '@wordpress/data';
import { compose, withPreferredColorScheme } from '@wordpress/compose';
import {
	InnerBlocks,
	BlockControls,
	BlockVerticalAlignmentToolbar,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	UnsupportedFooterControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */
import styles from './editor.scss';
import { getEffectiveColumnWidth } from '../columns/utils';

function ColumnEdit( {
	attributes,
	setAttributes,
	hasChildren,
	isSelected,
	getStylesFromColorScheme,
	isParentSelected,
	contentStyle,
	columnWidths,
	selectedColumnIndex,
} ) {
	const { verticalAlignment } = attributes;

	const updateAlignment = ( alignment ) => {
		setAttributes( { verticalAlignment: alignment } );
	};

	const onWidthChange = ( width ) => {
		setAttributes( {
			width,
		} );
	};

	if ( ! isSelected && ! hasChildren ) {
		return (
			<View
				style={ [
					! isParentSelected &&
						getStylesFromColorScheme(
							styles.columnPlaceholder,
							styles.columnPlaceholderDark
						),
					contentStyle,
					styles.columnPlaceholderNotSelected,
				] }
			/>
		);
	}

	const getRangePreview = () => {
		const columnsPreviewStyle = getStylesFromColorScheme(
			styles.columnsPreview,
			styles.columnsPreviewDark
		);

		const columnIndicatorStyle = getStylesFromColorScheme(
			styles.columnIndicator,
			styles.columnIndicatorDark
		);

		return (
			<View style={ columnsPreviewStyle }>
				{ columnWidths.map( ( width, index ) => {
					const isSelectedColumn = index === selectedColumnIndex;
					return (
						<View
							style={ [
								isSelectedColumn && columnIndicatorStyle,
								{ flex: width },
							] }
							key={ index }
						/>
					);
				} ) }
			</View>
		);
	};

	return (
		<>
			<BlockControls>
				<BlockVerticalAlignmentToolbar
					onChange={ updateAlignment }
					value={ verticalAlignment }
				/>
			</BlockControls>
			<InspectorControls>
				<PanelBody title={ __( 'Column settings' ) }>
					<RangeControl
						label={ __( 'Percentage width' ) }
						min={ 1 }
						max={ 100 }
						step={ 0.1 }
						value={ columnWidths[ selectedColumnIndex ] }
						onChange={ onWidthChange }
						toFixed={ 1 }
						rangePreview={ getRangePreview() }
					/>
				</PanelBody>
				<PanelBody>
					<UnsupportedFooterControl
						label={ __(
							'Note: Column layout may vary between themes and screen sizes'
						) }
						textAlign="center"
					/>
				</PanelBody>
			</InspectorControls>
			<View
				style={ [
					contentStyle,
					isSelected && hasChildren && styles.innerBlocksBottomSpace,
				] }
			>
				<InnerBlocks
					renderAppender={
						isSelected && InnerBlocks.ButtonBlockAppender
					}
				/>
			</View>
		</>
	);
}

function ColumnEditWrapper( props ) {
	const { verticalAlignment } = props.attributes;

	const getVerticalAlignmentRemap = ( alignment ) => {
		if ( ! alignment ) return styles.flexBase;
		return {
			...styles.flexBase,
			...styles[ `is-vertically-aligned-${ alignment }` ],
		};
	};

	return (
		<View style={ getVerticalAlignmentRemap( verticalAlignment ) }>
			<ColumnEdit { ...props } />
		</View>
	);
}

export default compose( [
	withSelect( ( select, { clientId } ) => {
		const {
			getBlockCount,
			getBlockRootClientId,
			getSelectedBlockClientId,
			getBlocks,
			getBlockOrder,
		} = select( 'core/block-editor' );

		const selectedBlockClientId = getSelectedBlockClientId();
		const isSelected = selectedBlockClientId === clientId;

		const parentId = getBlockRootClientId( clientId );
		const hasChildren = !! getBlockCount( clientId );
		const isParentSelected =
			selectedBlockClientId && selectedBlockClientId === parentId;

		const blockOrder = getBlockOrder( parentId );

		const selectedColumnIndex = blockOrder.indexOf( clientId );
		const columnCount = getBlockCount( parentId );
		const columnWidths = getBlocks( parentId ).map( ( column ) =>
			getEffectiveColumnWidth( column, columnCount )
		);

		return {
			hasChildren,
			isParentSelected,
			isSelected,
			selectedColumnIndex,
			columnWidths,
		};
	} ),
	withPreferredColorScheme,
] )( ColumnEditWrapper );
