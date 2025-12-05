/**
 * AutoBlogger Editor Entry Point
 * Registers Gutenberg sidebar panel
 */

import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-post';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import './style.scss';

// Import components (these would be created separately)
// import SidebarPanel from './SidebarPanel';

registerPlugin('autoblogger', {
    render: () => {
        return (
            <>
                <PluginSidebarMoreMenuItem target="autoblogger-sidebar">
                    {__('AutoBlogger', 'autoblogger')}
                </PluginSidebarMoreMenuItem>
                <PluginSidebar
                    name="autoblogger-sidebar"
                    title={__('AutoBlogger', 'autoblogger')}
                    icon="edit-large"
                >
                    <PanelBody>
                        <div className="autoblogger-sidebar">
                            <p>{__('AutoBlogger AI Content Generator', 'autoblogger')}</p>
                            <p style={{ fontSize: '12px', color: '#666' }}>
                                {__('Configure your API key in Settings to start using AI features.', 'autoblogger')}
                            </p>
                        </div>
                    </PanelBody>
                </PluginSidebar>
            </>
        );
    }
});

