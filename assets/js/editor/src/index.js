/**
 * AutoBlogger Editor Entry Point
 * Registers Gutenberg sidebar panel
 */

import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';
import './style.scss';

// Import sidebar component
import SidebarPanel from './components/SidebarPanel';

// Custom icon SVG (Yin-Yang)
const YinYangIcon = () => (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4M12,6A6,6 0 0,1 18,12H12A2,2 0 0,1 10,10A2,2 0 0,1 12,8A2,2 0 0,1 14,10A2,2 0 0,1 12,12A6,6 0 0,1 6,12A6,6 0 0,1 12,6M12,14A2,2 0 0,1 14,16A2,2 0 0,1 12,18A2,2 0 0,1 10,16A2,2 0 0,1 12,14Z" />
    </svg>
);

registerPlugin('autoblogger', {
    render: () => {
        return (
            <>
                <PluginSidebarMoreMenuItem target="autoblogger-sidebar" icon={<YinYangIcon />}>
                    {__('AutoBlogger', 'autoblogger')}
                </PluginSidebarMoreMenuItem>
                <PluginSidebar
                    name="autoblogger-sidebar"
                    title={__('AutoBlogger AI', 'autoblogger')}
                    icon={<YinYangIcon />}
                >
                    <SidebarPanel />
                </PluginSidebar>
            </>
        );
    }
});

