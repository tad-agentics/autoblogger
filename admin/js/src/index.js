/**
 * AutoBlogger Admin Entry Point
 * Renders admin dashboard pages
 */

import { render } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import './style.scss';

// Simple placeholder components
const SettingsPage = () => (
    <div className="autoblogger-admin">
        <h2>{__('Settings', 'autoblogger')}</h2>
        <p>{__('Configure your AutoBlogger settings here.', 'autoblogger')}</p>
    </div>
);

const KnowledgeBasePage = () => (
    <div className="autoblogger-admin">
        <h2>{__('Knowledge Base', 'autoblogger')}</h2>
        <p>{__('Manage your knowledge base entries.', 'autoblogger')}</p>
    </div>
);

const UsageDashboard = () => (
    <div className="autoblogger-admin">
        <h2>{__('Usage & Costs', 'autoblogger')}</h2>
        <p>{__('View your API usage and costs.', 'autoblogger')}</p>
    </div>
);

// Render appropriate component based on current page
const currentPage = window.autobloggerAdmin?.currentPage || 'autoblogger';

const rootElement = document.getElementById('autoblogger-settings-root') ||
                    document.getElementById('autoblogger-knowledge-root') ||
                    document.getElementById('autoblogger-usage-root');

if (rootElement) {
    let Component;
    
    if (currentPage.includes('knowledge')) {
        Component = KnowledgeBasePage;
    } else if (currentPage.includes('usage')) {
        Component = UsageDashboard;
    } else {
        Component = SettingsPage;
    }
    
    render(<Component />, rootElement);
}

