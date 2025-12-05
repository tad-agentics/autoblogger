import { registerBlockType } from '@wordpress/blocks';
import { RichText, useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

registerBlockType('autoblogger/disclaimer', {
    edit: ({ attributes, setAttributes }) => {
        const blockProps = useBlockProps({
            className: 'autoblogger-disclaimer'
        });

        return (
            <div {...blockProps} style={{
                padding: '15px',
                backgroundColor: '#fff3cd',
                border: '1px solid #ffc107',
                borderRadius: '4px',
                marginBottom: '20px'
            }}>
                <strong style={{ display: 'block', marginBottom: '10px' }}>
                    ⚠️ {__('Disclaimer', 'autoblogger')}
                </strong>
                <RichText
                    tagName="p"
                    value={attributes.content}
                    onChange={(content) => setAttributes({ content })}
                    placeholder={__('Enter disclaimer text...', 'autoblogger')}
                    style={{ margin: 0 }}
                />
            </div>
        );
    },

    save: ({ attributes }) => {
        const blockProps = useBlockProps.save({
            className: 'autoblogger-disclaimer'
        });

        return (
            <div {...blockProps} style={{
                padding: '15px',
                backgroundColor: '#fff3cd',
                border: '1px solid #ffc107',
                borderRadius: '4px',
                marginBottom: '20px'
            }}>
                <strong style={{ display: 'block', marginBottom: '10px' }}>
                    ⚠️ Disclaimer
                </strong>
                <RichText.Content tagName="p" value={attributes.content} style={{ margin: 0 }} />
            </div>
        );
    }
});

