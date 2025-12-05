import { registerBlockType } from '@wordpress/blocks';
import { RichText, useBlockProps } from '@wordpress/block-editor';
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

registerBlockType('autoblogger/expert-note', {
    edit: ({ attributes, setAttributes }) => {
        const blockProps = useBlockProps({
            className: 'autoblogger-expert-note'
        });

        return (
            <div {...blockProps} style={{
                padding: '20px',
                backgroundColor: '#e7f3ff',
                border: '2px solid #2196F3',
                borderLeft: '5px solid #2196F3',
                borderRadius: '4px',
                marginBottom: '20px'
            }}>
                <div style={{ marginBottom: '10px' }}>
                    <TextControl
                        label={__('Expert Name', 'autoblogger')}
                        value={attributes.expertName}
                        onChange={(expertName) => setAttributes({ expertName })}
                        style={{ fontWeight: 'bold' }}
                    />
                </div>
                <RichText
                    tagName="div"
                    value={attributes.content}
                    onChange={(content) => setAttributes({ content })}
                    placeholder={__('Add expert commentary...', 'autoblogger')}
                />
            </div>
        );
    },

    save: ({ attributes }) => {
        const blockProps = useBlockProps.save({
            className: 'autoblogger-expert-note'
        });

        return (
            <div {...blockProps} style={{
                padding: '20px',
                backgroundColor: '#e7f3ff',
                border: '2px solid #2196F3',
                borderLeft: '5px solid #2196F3',
                borderRadius: '4px',
                marginBottom: '20px'
            }}>
                <strong style={{ display: 'block', marginBottom: '10px', color: '#1976D2' }}>
                    💡 {attributes.expertName}
                </strong>
                <RichText.Content tagName="div" value={attributes.content} />
            </div>
        );
    }
});

