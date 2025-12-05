/**
 * Usage Dashboard Component
 * Displays API usage statistics and costs
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const UsageDashboard = () => {
    const [usage, setUsage] = useState({
        today: { cost: 0, requests: 0, tokens_input: 0, tokens_output: 0 },
        week: { cost: 0, requests: 0, tokens_input: 0, tokens_output: 0 },
        month: { cost: 0, requests: 0, tokens_input: 0, tokens_output: 0 },
        recent: []
    });
    const [settings, setSettings] = useState({ daily_budget: 5.00 });
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadUsage();
        loadSettings();
    }, []);

    const loadUsage = async () => {
        try {
            const response = await apiFetch({
                path: '/autoblogger/v1/usage',
                method: 'GET'
            });
            
            if (response.success) {
                setUsage(response.data);
            }
        } catch (error) {
            console.error('Failed to load usage:', error);
        } finally {
            setLoading(false);
        }
    };

    const loadSettings = async () => {
        try {
            const response = await apiFetch({
                path: '/autoblogger/v1/settings',
                method: 'GET'
            });
            
            if (response.success) {
                setSettings(response.data);
            }
        } catch (error) {
            console.error('Failed to load settings:', error);
        }
    };

    const formatCost = (cost) => {
        return `$${parseFloat(cost).toFixed(4)}`;
    };

    const formatNumber = (num) => {
        return num.toLocaleString();
    };

    const getBudgetPercentage = () => {
        return (usage.today.cost / settings.daily_budget) * 100;
    };

    const getBudgetColor = () => {
        const percentage = getBudgetPercentage();
        if (percentage >= 90) return '#dc3232'; // Red
        if (percentage >= 70) return '#ffb900'; // Yellow
        return '#46b450'; // Green
    };

    if (loading) {
        return <div className="autoblogger-loading">{__('Loading usage data...', 'autoblogger')}</div>;
    }

    return (
        <div className="autoblogger-usage">
            <div className="autoblogger-stats-grid">
                {/* Today's Usage */}
                <div className="autoblogger-stat-card">
                    <h3>{__('Today', 'autoblogger')}</h3>
                    <div className="stat-value">{formatCost(usage.today.cost)}</div>
                    <div className="stat-details">
                        <div>{formatNumber(usage.today.requests)} {__('requests', 'autoblogger')}</div>
                        <div>{formatNumber(usage.today.tokens_input + usage.today.tokens_output)} {__('tokens', 'autoblogger')}</div>
                    </div>
                    
                    {/* Budget Bar */}
                    <div className="budget-bar">
                        <div className="budget-bar-label">
                            <span>{__('Daily Budget', 'autoblogger')}</span>
                            <span>{getBudgetPercentage().toFixed(1)}%</span>
                        </div>
                        <div className="budget-bar-track">
                            <div 
                                className="budget-bar-fill"
                                style={{ 
                                    width: `${Math.min(getBudgetPercentage(), 100)}%`,
                                    backgroundColor: getBudgetColor()
                                }}
                            />
                        </div>
                        <div className="budget-bar-limit">
                            {formatCost(usage.today.cost)} / {formatCost(settings.daily_budget)}
                        </div>
                    </div>
                </div>

                {/* This Week */}
                <div className="autoblogger-stat-card">
                    <h3>{__('This Week', 'autoblogger')}</h3>
                    <div className="stat-value">{formatCost(usage.week.cost)}</div>
                    <div className="stat-details">
                        <div>{formatNumber(usage.week.requests)} {__('requests', 'autoblogger')}</div>
                        <div>{formatNumber(usage.week.tokens_input + usage.week.tokens_output)} {__('tokens', 'autoblogger')}</div>
                    </div>
                </div>

                {/* This Month */}
                <div className="autoblogger-stat-card">
                    <h3>{__('This Month', 'autoblogger')}</h3>
                    <div className="stat-value">{formatCost(usage.month.cost)}</div>
                    <div className="stat-details">
                        <div>{formatNumber(usage.month.requests)} {__('requests', 'autoblogger')}</div>
                        <div>{formatNumber(usage.month.tokens_input + usage.month.tokens_output)} {__('tokens', 'autoblogger')}</div>
                    </div>
                </div>
            </div>

            {/* Recent Activity */}
            <div className="autoblogger-recent-activity">
                <h3>{__('Recent Activity', 'autoblogger')}</h3>
                
                {usage.recent.length === 0 ? (
                    <p className="description">
                        {__('No activity yet. Start generating content to see usage statistics!', 'autoblogger')}
                    </p>
                ) : (
                    <table className="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style={{ width: '20%' }}>{__('Date/Time', 'autoblogger')}</th>
                                <th style={{ width: '20%' }}>{__('Operation', 'autoblogger')}</th>
                                <th style={{ width: '15%' }}>{__('Input Tokens', 'autoblogger')}</th>
                                <th style={{ width: '15%' }}>{__('Output Tokens', 'autoblogger')}</th>
                                <th style={{ width: '15%' }}>{__('Cost', 'autoblogger')}</th>
                                <th style={{ width: '15%' }}>{__('User', 'autoblogger')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {usage.recent.map((item, index) => (
                                <tr key={index}>
                                    <td>{new Date(item.created_at).toLocaleString()}</td>
                                    <td><code>{item.operation}</code></td>
                                    <td>{formatNumber(item.tokens_input)}</td>
                                    <td>{formatNumber(item.tokens_output)}</td>
                                    <td><strong>{formatCost(item.cost)}</strong></td>
                                    <td>{item.user_name || `User #${item.user_id}`}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>

            {/* Cost Breakdown */}
            <div className="autoblogger-cost-breakdown">
                <h3>{__('Cost Breakdown (This Month)', 'autoblogger')}</h3>
                <table className="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">{__('Input Tokens', 'autoblogger')}</th>
                            <td>
                                {formatNumber(usage.month.tokens_input)} tokens
                                <span style={{ marginLeft: '20px', color: '#666' }}>
                                    (~{formatCost(usage.month.tokens_input * 0.000003)})
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{__('Output Tokens', 'autoblogger')}</th>
                            <td>
                                {formatNumber(usage.month.tokens_output)} tokens
                                <span style={{ marginLeft: '20px', color: '#666' }}>
                                    (~{formatCost(usage.month.tokens_output * 0.000015)})
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><strong>{__('Total Cost', 'autoblogger')}</strong></th>
                            <td><strong>{formatCost(usage.month.cost)}</strong></td>
                        </tr>
                        <tr>
                            <th scope="row">{__('Average per Request', 'autoblogger')}</th>
                            <td>
                                {usage.month.requests > 0 
                                    ? formatCost(usage.month.cost / usage.month.requests)
                                    : '$0.00'
                                }
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {/* Budget Warning */}
            {getBudgetPercentage() >= 90 && (
                <div className="notice notice-error">
                    <p>
                        <strong>{__('Budget Warning!', 'autoblogger')}</strong>
                        {' '}
                        {__('You have used', 'autoblogger')} {getBudgetPercentage().toFixed(1)}% {__('of your daily budget.', 'autoblogger')}
                    </p>
                </div>
            )}
        </div>
    );
};

export default UsageDashboard;

