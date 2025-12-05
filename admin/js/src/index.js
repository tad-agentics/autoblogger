/**
 * AutoBlogger Admin Entry Point
 * Renders admin dashboard pages
 */

import { render, createElement } from '@wordpress/element';
import './style.scss';

// Import components
import SettingsPage from './components/SettingsPage';
import KnowledgeBasePage from './components/KnowledgeBasePage';
import UsageDashboard from './components/UsageDashboard';

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
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
        
        render(createElement(Component), rootElement);
    }
});

